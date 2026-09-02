<?php

namespace Database\Seeders;

use Database\Seeders\Development\AreasAndTablesSeeder;
use Database\Seeders\Development\CatalogSeeder;
use Database\Seeders\Development\ProductionSeeder;
use Database\Seeders\Development\StaffSeeder;
use Illuminate\Database\Seeder;

/**
 * The single entry point `php artisan db:seed` calls. Keeps two concerns
 * separate — see docs/database.md "Seeders":
 *
 *  - Essential (database/seeders/Essential/*): data every installation
 *    needs to function. Must be idempotent (updateOrCreate or
 *    equivalent) since it can run again on an installation that already
 *    has data, not just once on a fresh database.
 *  - Development (database/seeders/Development/*): local/demo data.
 *    Only runs in local/testing — never confused with essential data on
 *    a real restaurant's installation.
 *
 * Areas and tables add local/testing demo data below. Production still
 * receives no commercial areas or physical table definitions.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->runEssentialSeeders();

        if (app()->environment(['local', 'testing'])) {
            $this->runDevelopmentSeeders();
        }
    }

    /**
     * Data every installation needs. Must stay safe to run repeatedly,
     * including against a database that already has this data.
     */
    private function runEssentialSeeders(): void
    {
        // $this->call([
        //     \Database\Seeders\Essential\...Seeder::class,
        // ]);
    }

    /**
     * Local/demo data only — gated to local/testing in run() above.
     */
    private function runDevelopmentSeeders(): void
    {
        $this->call([
            AreasAndTablesSeeder::class,
            CatalogSeeder::class,
            StaffSeeder::class,
            ProductionSeeder::class,
        ]);
    }
}
