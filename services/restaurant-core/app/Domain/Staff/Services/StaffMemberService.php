<?php

namespace App\Domain\Staff\Services;

use App\Domain\Mobile\Models\WaiterAccessToken;
use App\Domain\Staff\Models\StaffMember;
use App\Support\Http\ApiException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Hash;

final class StaffMemberService
{
    public function list(): Collection
    {
        return StaffMember::query()->where('staff_type', StaffMember::TYPE_WAITER)->orderByDesc('is_active')->orderBy('name')->get();
    }

    public function create(array $data): StaffMember
    {
        return StaffMember::query()->create(['name' => $data['name'], 'normalized_name' => $data['normalizedName'], 'staff_type' => StaffMember::TYPE_WAITER, 'is_active' => $data['isActive'] ?? true, 'username' => isset($data['username']) ? mb_strtolower(trim($data['username'])) : null, 'pin_hash' => isset($data['pin']) ? Hash::make($data['pin']) : null]);
    }

    public function update(StaffMember $staff, array $data): StaffMember
    {
        if (($data['isActive'] ?? true) === false && $staff->openSessions()->exists()) {
            throw new ApiException('WAITER_HAS_OPEN_SESSIONS', 'No puedes desactivar un mesero con mesas abiertas.', 409);
        }
        $staff->update(array_filter(['name' => $data['name'] ?? null, 'normalized_name' => $data['normalizedName'] ?? null, 'is_active' => $data['isActive'] ?? null, 'username' => isset($data['username']) ? mb_strtolower(trim($data['username'])) : null, 'pin_hash' => isset($data['pin']) ? Hash::make($data['pin']) : null], fn ($value) => $value !== null));
        if (($data['isActive'] ?? true) === false) {
            WaiterAccessToken::query()->where('staff_member_id', $staff->id)->whereNull('revoked_at')->update(['revoked_at' => now()]);
        }

        return $staff->refresh();
    }
}
