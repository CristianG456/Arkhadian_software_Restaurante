<?php

namespace App\Domain\Mobile\Services;

use App\Domain\Mobile\Models\WaiterAccessToken;
use App\Domain\Mobile\Models\WaiterDevice;
use App\Domain\Staff\Models\StaffMember;
use App\Support\Http\ApiException;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

final class WaiterAuthService
{
    public function login(WaiterDevice $device, string $username, string $pin): array
    {
        $staff = StaffMember::query()->where('username', mb_strtolower(trim($username)))->first();
        if (! $staff || ! $staff->pin_hash || ! Hash::check($pin, $staff->pin_hash)) {
            throw new ApiException('INVALID_CREDENTIALS', 'Usuario o PIN incorrectos.', 401);
        }
        if ($staff->staff_type !== StaffMember::TYPE_WAITER) {
            throw new ApiException('UNAUTHORIZED_ROLE', 'Este usuario no tiene acceso a la app Mesero.', 403);
        }
        if (! $staff->is_active) {
            throw new ApiException('WAITER_INACTIVE', 'Tu usuario ya no está habilitado.', 403);
        }

        return ['staff' => $staff, ...$this->issue($staff, $device)];
    }

    public function issue(StaffMember $staff, WaiterDevice $device): array
    {
        $plain = Str::random(80);
        $expires = now()->addHours(12);
        $token = WaiterAccessToken::query()->create(['staff_member_id' => $staff->id, 'device_id' => $device->id, 'token_hash' => hash('sha256', $plain), 'expires_at' => $expires]);

        return ['accessToken' => $plain, 'expiresAt' => $expires->toIso8601String(), 'token' => $token];
    }

    public function rotate(WaiterAccessToken $token): array
    {
        $token->update(['revoked_at' => now()]);

        return ['staff' => $token->staff, ...$this->issue($token->staff, $token->device)];
    }
}
