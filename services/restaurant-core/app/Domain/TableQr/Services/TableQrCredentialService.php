<?php

namespace App\Domain\TableQr\Services;

use App\Domain\TableQr\Models\TableQrCredential;
use App\Domain\Tables\Models\RestaurantTable;
use App\Support\Http\ApiException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

final class TableQrCredentialService
{
    public function current(RestaurantTable $table): TableQrCredential
    {
        $credential = $table->activeQrCredential()->first();

        if ($credential === null) {
            throw new ApiException('QR_NOT_FOUND', 'Esta mesa no tiene un QR activo.', 404);
        }

        return $credential;
    }

    public function generate(RestaurantTable $table): TableQrCredential
    {
        return DB::transaction(function () use ($table): TableQrCredential {
            $lockedTable = RestaurantTable::query()->lockForUpdate()->findOrFail($table->id);
            if ($lockedTable->activeQrCredential()->exists()) {
                throw new ApiException('QR_ALREADY_ACTIVE', 'Esta mesa ya tiene un QR activo.', 409);
            }

            return $this->createNextVersion($lockedTable);
        });
    }

    public function regenerate(RestaurantTable $table): TableQrCredential
    {
        return DB::transaction(function () use ($table): TableQrCredential {
            $lockedTable = RestaurantTable::query()->lockForUpdate()->findOrFail($table->id);
            $active = $lockedTable->activeQrCredential()->lockForUpdate()->first();
            if ($active === null) {
                throw new ApiException('QR_NOT_FOUND', 'Esta mesa no tiene un QR activo para regenerar.', 404);
            }

            $active->update([
                'status' => TableQrCredential::STATUS_REVOKED,
                'revoked_at' => now(),
            ]);

            return $this->createNextVersion($lockedTable);
        });
    }

    public function revoke(RestaurantTable $table): TableQrCredential
    {
        return DB::transaction(function () use ($table): TableQrCredential {
            $lockedTable = RestaurantTable::query()->lockForUpdate()->findOrFail($table->id);
            $active = $lockedTable->activeQrCredential()->lockForUpdate()->first();
            if ($active === null) {
                throw new ApiException('QR_NOT_FOUND', 'Esta mesa no tiene un QR activo para revocar.', 404);
            }

            $active->update([
                'status' => TableQrCredential::STATUS_REVOKED,
                'revoked_at' => now(),
            ]);

            return $active->refresh();
        });
    }

    private function createNextVersion(RestaurantTable $table): TableQrCredential
    {
        $version = ((int) $table->qrCredentials()->max('version')) + 1;

        try {
            return $table->qrCredentials()->create([
                'public_token' => $this->generateToken(),
                'version' => $version,
                'status' => TableQrCredential::STATUS_ACTIVE,
                'revoked_at' => null,
            ]);
        } catch (QueryException $exception) {
            if ((string) $exception->getCode() === '23505') {
                throw new ApiException(
                    'QR_CONCURRENT_CHANGE',
                    'El QR cambió en otra operación. Vuelve a cargar e inténtalo de nuevo.',
                    409,
                    $exception,
                );
            }

            throw $exception;
        }
    }

    private function generateToken(): string
    {
        return rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
    }
}
