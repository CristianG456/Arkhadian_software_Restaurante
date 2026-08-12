<?php

namespace Tests\Unit\Domain\System;

use App\Domain\System\Services\InstallationIdentityService;
use Tests\TestCase;

class InstallationIdentityServiceTest extends TestCase
{
    private string $path;

    protected function setUp(): void
    {
        parent::setUp();

        $this->path = sys_get_temp_dir().'/restaurant_core_test_installation_'.uniqid().'.json';
    }

    protected function tearDown(): void
    {
        if (is_file($this->path)) {
            unlink($this->path);
        }

        parent::tearDown();
    }

    public function test_it_generates_an_id_on_first_use(): void
    {
        $service = new InstallationIdentityService($this->path);

        $id = $service->id();

        $this->assertNotEmpty($id);
        $this->assertFileExists($this->path);
    }

    public function test_it_persists_the_same_id_across_instances(): void
    {
        $first = (new InstallationIdentityService($this->path))->id();
        $second = (new InstallationIdentityService($this->path))->id();

        $this->assertSame($first, $second);
    }

    public function test_the_id_is_not_derived_from_predictable_input(): void
    {
        $idA = (new InstallationIdentityService($this->path))->id();

        unlink($this->path);
        $idB = (new InstallationIdentityService($this->path))->id();

        $this->assertNotSame($idA, $idB);
    }
}
