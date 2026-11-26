<?php

namespace App\Console\Commands;

use App\Models\Portal;
use App\Services\TenantManager;
use App\Services\TenantProvisioner;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

class TenantsSyncViews extends Command
{
    protected $signature = 'tenants:sync-views';
    protected $description = 'Create/refresh the views (database/tenant/views.sql) in all existing tenant databases';

    public function handle(TenantProvisioner $provisioner): int
    {
        $failed = 0;

        Portal::whereNotNull('database_name')->each(function (Portal $portal) use ($provisioner, &$failed) {
            try {
                if (!$provisioner->databaseExists($portal->database_name)) {
                    $this->warn("SKIP  {$portal->database_name} (database does not exist - use \"Create / repair tenant database\" in the admin panel)");
                    return;
                }

                TenantManager::connect($portal);
                $provisioner->createViews(DB::connection('tenant'));
                $this->info("OK    {$portal->database_name}");
            } catch (Throwable $e) {
                $failed++;
                $this->error("FAIL  {$portal->database_name}: {$e->getMessage()}");
            }
        });

        return $failed ? self::FAILURE : self::SUCCESS;
    }
}
