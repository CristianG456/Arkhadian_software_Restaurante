<?php

namespace App\Domain\Mobile\Services;

use App\Domain\Mobile\Models\DevicePairing;
use App\Domain\Mobile\Models\WaiterDevice;
use App\Support\Http\ApiException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

final class PairingService
{
    public function __construct(private readonly RestaurantContextService $restaurants) {}

    public function create(int $minutes = 10): array
    {
        $installation = $this->restaurants->current();
        $code = (string) random_int(100000, 999999);
        $token = Str::random(64);
        $pairing = DevicePairing::query()->create([
            'installation_id' => $installation->id,
            'code_hash' => Hash::make($code),
            'token_hash' => hash('sha256', $token),
            'max_uses' => 1,
            'use_count' => 0,
            'expires_at' => now()->addMinutes($minutes),
        ]);

        return ['pairing' => $pairing, 'code' => $code, 'token' => $token, 'installation' => $installation, 'qrPayload' => json_encode(['type' => 'RESTAURANT_DEVICE_PAIRING', 'version' => 1, 'restaurantCode' => $installation->restaurant_code, 'pairingToken' => $token, 'coreEndpoint' => config('system.mobile_core_url')], JSON_THROW_ON_ERROR)];
    }

    public function resolve(string $restaurantCode, ?string $code, ?string $token): array
    {
        $installation = $this->restaurants->current();
        if (! hash_equals($installation->restaurant_code, strtoupper(trim($restaurantCode)))) {
            throw new ApiException('RESTAURANT_NOT_FOUND', 'El restaurante indicado no corresponde a este servidor.', 404);
        }

        $pairing = $this->validPairing($installation->id, $code, $token);

        return ['installation' => $installation, 'pairing' => $pairing];
    }

    public function pair(array $data): array
    {
        ['installation' => $installation, 'pairing' => $pairing] = $this->resolve(
            $data['restaurantCode'],
            $data['pairingCode'] ?? null,
            $data['pairingToken'] ?? null,
        );

        return DB::transaction(function () use ($data, $installation, $pairing): array {
            $locked = DevicePairing::query()->lockForUpdate()->findOrFail($pairing->id);
            if ($locked->use_count >= $locked->max_uses || $locked->used_at) {
                throw new ApiException('PAIRING_CODE_USED', 'Este código de vinculación ya fue utilizado.', 409);
            }
            $credential = Str::random(80);
            $device = WaiterDevice::query()->updateOrCreate(
                ['installation_id' => $installation->id, 'device_identifier' => $data['deviceIdentifier']],
                ['name' => trim($data['deviceName']), 'platform' => strtoupper($data['platform']), 'credential_hash' => hash('sha256', $credential), 'status' => WaiterDevice::STATUS_ACTIVE, 'paired_at' => now(), 'revoked_at' => null],
            );
            $locked->update(['use_count' => $locked->use_count + 1, 'used_at' => now()]);

            return ['device' => $device, 'deviceToken' => $credential, 'installation' => $installation];
        });
    }

    private function validPairing(string $installationId, ?string $code, ?string $token): DevicePairing
    {
        $query = DevicePairing::query()->where('installation_id', $installationId)->latest()->limit(50);
        if ($token) {
            $pairing = (clone $query)->where('token_hash', hash('sha256', $token))->first();
        } else {
            $pairing = $query->get()->first(fn (DevicePairing $candidate) => $code && Hash::check($code, $candidate->code_hash));
        }
        if (! $pairing) {
            throw new ApiException('PAIRING_CODE_INVALID', 'El código de vinculación no es válido.', 422);
        }
        if ($pairing->used_at || $pairing->use_count >= $pairing->max_uses) {
            throw new ApiException('PAIRING_CODE_USED', 'Este código de vinculación ya fue utilizado.', 409);
        }
        if (DevicePairing::query()->whereKey($pairing->id)->where('expires_at', '<=', now())->exists()) {
            throw new ApiException('PAIRING_CODE_EXPIRED', 'El código de vinculación venció.', 422);
        }

        return $pairing;
    }
}
