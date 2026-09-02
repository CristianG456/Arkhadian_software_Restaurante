<?php

namespace App\Domain\Mobile\Http\Controllers;

use App\Domain\Mobile\Models\WaiterDevice;
use App\Domain\Mobile\Services\PairingService;
use App\Domain\Mobile\Services\RestaurantContextService;
use App\Http\Controllers\Controller;
use App\Support\Http\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PairingAdminController extends Controller
{
    public function installation(RestaurantContextService $restaurants): JsonResponse
    {
        $installation = $restaurants->current();

        return ApiResponse::success(['installation' => ['id' => $installation->id, 'restaurantCode' => $installation->restaurant_code, 'restaurantName' => $installation->restaurant_name, 'operatingMode' => $installation->operating_mode]]);
    }

    public function create(Request $request, PairingService $pairings): JsonResponse
    {
        $data = $request->validate(['minutes' => ['sometimes', 'integer', 'between:2,30']]);
        $result = $pairings->create($data['minutes'] ?? 10);

        return ApiResponse::success(['pairing' => ['id' => $result['pairing']->id, 'restaurantCode' => $result['installation']->restaurant_code, 'pairingCode' => $result['code'], 'pairingToken' => $result['token'], 'qrPayload' => $result['qrPayload'], 'expiresAt' => $result['pairing']->expires_at->toIso8601String()]], 201);
    }

    public function devices(RestaurantContextService $restaurants): JsonResponse
    {
        $devices = WaiterDevice::query()->where('installation_id', $restaurants->current()->id)->latest('paired_at')->get()->map(fn (WaiterDevice $device) => $this->device($device))->all();

        return ApiResponse::success(['devices' => $devices]);
    }

    public function update(Request $request, WaiterDevice $device): JsonResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'min:2', 'max:100']]);
        $device->update(['name' => trim($data['name'])]);

        return ApiResponse::success(['device' => $this->device($device->refresh())]);
    }

    public function revoke(WaiterDevice $device): JsonResponse
    {
        $device->update(['status' => WaiterDevice::STATUS_REVOKED, 'revoked_at' => now()]);
        $device->accessTokens()->whereNull('revoked_at')->update(['revoked_at' => now()]);

        return ApiResponse::success(['device' => $this->device($device->refresh())]);
    }

    private function device(WaiterDevice $device): array
    {
        return ['id' => $device->id, 'name' => $device->name, 'platform' => $device->platform, 'status' => $device->status, 'pairedAt' => $device->paired_at->toIso8601String(), 'lastSeenAt' => $device->last_seen_at?->toIso8601String(), 'revokedAt' => $device->revoked_at?->toIso8601String()];
    }
}
