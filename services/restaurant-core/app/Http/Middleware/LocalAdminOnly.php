<?php

namespace App\Http\Middleware;

use App\Support\Http\ApiException;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LocalAdminOnly
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! in_array($request->ip(), ['127.0.0.1', '::1'], true)) {
            throw new ApiException('LOCAL_ADMIN_ONLY', 'Esta operación administrativa solo está disponible desde este equipo.', 403);
        }

        return $next($request);
    }
}
