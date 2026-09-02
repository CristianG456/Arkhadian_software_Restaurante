<?php

namespace App\Domain\Mobile\Services;

use App\Domain\Mobile\Models\RestaurantInstallation;
use App\Domain\System\Services\InstallationIdentityService;

final class RestaurantContextService
{
    public function __construct(private readonly InstallationIdentityService $identity) {}

    public function current(): RestaurantInstallation
    {
        $id = $this->identity->id();
        $code = 'ARK-'.strtoupper(substr(hash('sha256', $id), 0, 6));

        return RestaurantInstallation::query()->firstOrCreate(
            ['id' => $id],
            ['restaurant_code' => $code, 'restaurant_name' => config('restaurant.business_name', 'Restaurant Admin'), 'operating_mode' => 'LOCAL'],
        );
    }
}
