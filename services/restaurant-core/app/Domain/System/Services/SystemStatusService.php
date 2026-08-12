<?php

namespace App\Domain\System\Services;

/**
 * Aggregates the four independent signals Admin Desktop's status screen
 * needs. Each sub-check fails in isolation — a dead database must not
 * prevent reporting that Core and the local server are otherwise fine.
 */
final class SystemStatusService
{
    public function __construct(
        private readonly DatabaseHealthService $database,
        private readonly InternetConnectivityService $internet,
        private readonly InstallationIdentityService $installation,
    ) {}

    public function snapshot(): array
    {
        return [
            'core' => [
                'status' => app()->isDownForMaintenance() ? 'degraded' : 'online',
                'version' => config('system.version'),
            ],
            'database' => $this->database->check(),
            'localServer' => [
                'status' => 'online',
                'host' => config('system.host'),
                'port' => (int) config('system.port'),
            ],
            'internet' => $this->internet->check(),
            'installationId' => $this->installation->id(),
            'checkedAt' => now()->toIso8601String(),
        ];
    }
}
