<?php

namespace App\Http\Middleware;

use App\Domain\Mobile\Models\WaiterDevice;
use App\Support\Http\ApiException;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PairedWaiterDevice
{
    public function handle(Request $request, Closure $next): Response
    {
        $plain = $request->header('X-Device-Token');
        if (! is_string($plain) || $plain === '') {
            throw new ApiException('DEVICE_NOT_PAIRED', 'Este dispositivo no está vinculado.', 401);
        }
        $device = WaiterDevice::query()->with('installation')->where('credential_hash', hash('sha256', $plain))->first();
        if (! $device) {
            throw new ApiException('DEVICE_NOT_PAIRED', 'La credencial del dispositivo no es válida.', 401);
        }
        if ($device->status !== WaiterDevice::STATUS_ACTIVE) {
            throw new ApiException('DEVICE_REVOKED', 'Este dispositivo fue desvinculado del restaurante.', 401);
        }
        $device->update(['last_seen_at' => now()]);
        $request->attributes->set('waiterDevice', $device);

        return $next($request);
    }
}
