<?php

namespace App\Domain\Staff\Services;

use App\Domain\Staff\Models\StaffMember;

final class OperationalPermissionService
{
    public const DEFAULT_WAITER = ['orders.create', 'orders.view_own', 'tables.transfer_own', 'tables.move_own', 'preaccount.view', 'preaccount.print'];

    public const CONFIGURABLE = ['orders.cancel', 'orders.reprint'];

    public function allows(StaffMember $staff, string $permission): bool
    {
        $override = $staff->permissions()->where('permission', $permission)->first();

        return $override ? $override->is_granted : in_array($permission, self::DEFAULT_WAITER, true);
    }
}
