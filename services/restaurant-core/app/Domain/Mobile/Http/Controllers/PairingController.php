<?php

namespace App\Domain\Mobile\Http\Controllers;

use App\Domain\Mobile\Services\PairingService;
use App\Http\Controllers\Controller;
use App\Support\Http\ApiException;
use App\Support\Http\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PairingController extends Controller
{
    public function resolve(Request $request, PairingService $pairings): JsonResponse
    {
        $data = $request->validate(['restaurantCode' => ['required', 'string', 'max:24'], 'pairingCode' => ['nullable', 'digits:6', 'required_without:pairingToken'], 'pairingToken' => ['nullable', 'string', 'min:32', 'required_without:pairingCode']]);
        $result = $pairings->resolve($data['restaurantCode'], $data['pairingCode'] ?? null, $data['pairingToken'] ?? null);

        return ApiResponse::success(['restaurant' => ['name' => $result['installation']->restaurant_name, 'code' => $result['installation']->restaurant_code, 'operatingMode' => $result['installation']->operating_mode]]);
    }

    public function pair(Request $request, PairingService $pairings): JsonResponse
    {
        $data = $request->validate(['restaurantCode' => ['required', 'string', 'max:24'], 'pairingCode' => ['nullable', 'digits:6', 'required_without:pairingToken'], 'pairingToken' => ['nullable', 'string', 'min:32', 'required_without:pairingCode'], 'deviceName' => ['required', 'string', 'min:2', 'max:100'], 'deviceIdentifier' => ['required', 'uuid'], 'platform' => ['required', 'in:ANDROID,IOS']]);
        try {
            $result = $pairings->pair($data);
        } catch (ApiException $exception) {
            return ApiResponse::error($exception->code(), $exception->getMessage(), $exception->status());
        }

        return ApiResponse::success(['device' => ['id' => $result['device']->id, 'name' => $result['device']->name, 'platform' => $result['device']->platform], 'deviceToken' => $result['deviceToken'], 'restaurant' => ['id' => $result['installation']->id, 'name' => $result['installation']->restaurant_name, 'code' => $result['installation']->restaurant_code, 'operatingMode' => $result['installation']->operating_mode]], 201);
    }
}
