<?php

// Centralized, non-secret configuration for Restaurant Core itself: identity,
// version, network binding and the health/status checks used by Admin
// Desktop's system screen. Nothing here belongs to a specific business
// module (Orders, Tables, ...) — it exists before any of those do.

return [

    'name' => env('APP_NAME', 'Restaurant Core'),

    // Single source of truth for the Core's version. Admin Desktop has its
    // own independent version (apps/admin-desktop/package.json) — see
    // docs/architecture.md "Versioning".
    'version' => env('APP_VERSION', '0.1.0'),

    /*
    |--------------------------------------------------------------------------
    | Local network binding
    |--------------------------------------------------------------------------
    |
    | The host/port Restaurant Core listens on. In development this is
    | 127.0.0.1; in production it is bindable on the LAN interface so that
    | future LAN clients (waiter tablets, kitchen displays) can reach it.
    | Device authorization for those LAN clients is a later phase — Phase 1
    | only centralizes where the value comes from.
    |
    */
    'host' => env('CORE_HOST', '127.0.0.1'),
    'port' => env('CORE_PORT', 8000),
    'mobile_core_url' => env('MOBILE_CORE_URL', 'http://10.0.2.2:8000'),

    /*
    |--------------------------------------------------------------------------
    | Installation identity
    |--------------------------------------------------------------------------
    |
    | A random identifier generated on first boot and persisted to disk
    | (not derived from MAC address / disk serial). It survives database
    | resets and restores, which is why it lives in storage/ rather than
    | in a table. See App\Domain\System\Services\InstallationIdentityService.
    |
    */
    'installation_id_path' => storage_path('app/private/installation.json'),

    /*
    |--------------------------------------------------------------------------
    | Internet reachability probe
    |--------------------------------------------------------------------------
    |
    | Internet absence is expected and not an error. We probe a short list
    | of well-known DNS resolvers on port 53 with a short timeout instead of
    | trusting a single arbitrary domain/HTTP endpoint.
    |
    */
    'internet_check' => [
        'targets' => array_map(
            static function (string $target): array {
                [$host, $port] = array_pad(explode(':', trim($target)), 2, 53);

                return ['host' => $host, 'port' => (int) $port];
            },
            explode(',', env('INTERNET_CHECK_HOSTS', '1.1.1.1:53,8.8.8.8:53'))
        ),
        'timeout' => (float) env('INTERNET_CHECK_TIMEOUT', 1.5),
    ],
];
