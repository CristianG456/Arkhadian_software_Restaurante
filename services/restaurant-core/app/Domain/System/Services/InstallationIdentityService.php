<?php

namespace App\Domain\System\Services;

use Illuminate\Support\Str;

/**
 * A per-installation identifier generated once on first boot and persisted
 * to disk — not derived from MAC address, disk serial, or any hardware
 * fingerprint. It is the anchor future phases (licensing, remote support,
 * cloud sync) use to recognize "this restaurant's install" and must survive
 * database restores, which is why it does not live in a table.
 */
final class InstallationIdentityService
{
    public function __construct(private readonly string $path) {}

    public function id(): string
    {
        if (is_file($this->path)) {
            $decoded = json_decode((string) file_get_contents($this->path), true);

            if (is_array($decoded) && ! empty($decoded['installation_id'])) {
                return $decoded['installation_id'];
            }
        }

        return $this->create();
    }

    private function create(): string
    {
        $id = (string) Str::uuid();

        $directory = dirname($this->path);

        if (! is_dir($directory)) {
            mkdir($directory, 0755, recursive: true);
        }

        file_put_contents($this->path, json_encode([
            'installation_id' => $id,
            'created_at' => now()->toIso8601String(),
        ], JSON_PRETTY_PRINT));

        return $id;
    }
}
