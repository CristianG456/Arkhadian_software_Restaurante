<?php

namespace App\Domain\System\Services;

/**
 * A missing Internet connection is a normal, expected state for a
 * Local-First restaurant (see docs/architecture.md) — this only answers
 * "is there a route out", never treated as an application error.
 *
 * Deliberately checks TCP reachability to a short list of well-known
 * resolvers rather than trusting one arbitrary HTTP domain, and uses a
 * short timeout so it can never block a request for long.
 */
final class InternetConnectivityService
{
    /**
     * @param  array<int, array{host: string, port: int}>  $targets
     */
    public function __construct(
        private readonly array $targets,
        private readonly float $timeout,
    ) {}

    /**
     * @return array{status: 'online'|'offline'}
     */
    public function check(): array
    {
        foreach ($this->targets as $target) {
            if ($this->canReach($target['host'], $target['port'])) {
                return ['status' => 'online'];
            }
        }

        return ['status' => 'offline'];
    }

    private function canReach(string $host, int $port): bool
    {
        $connection = @fsockopen($host, $port, $errno, $errstr, $this->timeout);

        if ($connection === false) {
            return false;
        }

        fclose($connection);

        return true;
    }
}
