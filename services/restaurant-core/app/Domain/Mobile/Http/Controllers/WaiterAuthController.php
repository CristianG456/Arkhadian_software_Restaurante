<?php

namespace App\Domain\Mobile\Http\Controllers;

use App\Domain\Mobile\Models\WaiterAccessToken;
use App\Domain\Mobile\Models\WaiterDevice;
use App\Domain\Mobile\Services\WaiterAuthService;
use App\Domain\Staff\Models\StaffMember;
use App\Http\Controllers\Controller;
use App\Support\Http\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WaiterAuthController extends Controller
{
    public function login(Request $request, WaiterAuthService $auth): JsonResponse
    {
        $data = $request->validate(['username' => ['required', 'string', 'max:60'], 'pin' => ['required', 'digits_between:4,8']]);
        /** @var WaiterDevice $device */
        $device = $request->attributes->get('waiterDevice');

        return ApiResponse::success($this->session($auth->login($device, $data['username'], $data['pin']), $device));
    }

    public function me(Request $request): JsonResponse
    {
        return ApiResponse::success(['user' => $this->staff($request->attributes->get('waiter')), 'device' => $this->device($request->attributes->get('waiterDevice'))]);
    }

    public function refresh(Request $request, WaiterAuthService $auth): JsonResponse
    {
        /** @var WaiterAccessToken $token */
        $token = $request->attributes->get('waiterToken');

        return ApiResponse::success($this->session($auth->rotate($token), $request->attributes->get('waiterDevice')));
    }

    public function logout(Request $request): JsonResponse
    {
        $request->attributes->get('waiterToken')->update(['revoked_at' => now()]);

        return ApiResponse::success(['loggedOut' => true]);
    }

    private function session(array $result, WaiterDevice $device): array
    {
        return ['accessToken' => $result['accessToken'], 'expiresAt' => $result['expiresAt'], 'user' => $this->staff($result['staff']), 'device' => $this->device($device)];
    }

    private function staff(StaffMember $staff): array
    {
        return ['id' => $staff->id, 'name' => $staff->name, 'username' => $staff->username, 'role' => $staff->staff_type];
    }

    private function device(WaiterDevice $device): array
    {
        return ['id' => $device->id, 'name' => $device->name, 'platform' => $device->platform, 'restaurant' => ['name' => $device->installation->restaurant_name, 'code' => $device->installation->restaurant_code, 'operatingMode' => $device->installation->operating_mode]];
    }
}
