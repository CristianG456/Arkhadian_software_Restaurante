<?php

namespace App\Http\Middleware;

use App\Domain\Mobile\Models\WaiterAccessToken;
use App\Domain\Mobile\Models\WaiterDevice;
use App\Domain\Staff\Models\StaffMember;
use App\Support\Http\ApiException;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticatedWaiter
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var WaiterDevice|null $device */
        $device = $request->attributes->get('waiterDevice');
        $plain = $request->bearerToken();
        if (! $device || ! $plain) {
            throw new ApiException('UNAUTHENTICATED', 'Inicia sesión nuevamente.', 401);
        }
        $token = WaiterAccessToken::query()->with(['staff', 'device'])->where('token_hash', hash('sha256', $plain))->first();
        if (! $token || $token->device_id !== $device->id || $token->revoked_at || $token->expires_at->isPast()) {
            throw new ApiException('UNAUTHENTICATED', 'La sesión expiró. Inicia sesión nuevamente.', 401);
        }
        if ($token->staff->staff_type !== StaffMember::TYPE_WAITER) {
            throw new ApiException('UNAUTHORIZED_ROLE', 'Este usuario no tiene acceso a la app Mesero.', 403);
        }
        if (! $token->staff->is_active) {
            $token->update(['revoked_at' => now()]);
            throw new ApiException('WAITER_INACTIVE', 'Tu usuario ya no está habilitado.', 403);
        }
        $token->update(['last_used_at' => now()]);
        $request->attributes->set('waiterToken', $token);
        $request->attributes->set('waiter', $token->staff);

        return $next($request);
    }
}
