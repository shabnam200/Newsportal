<?php

namespace App\Services;

use App\Models\Portal;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

class TenantManager
{
    /** Point the "tenant" connection at this portal's database. */
    public static function connect(Portal $portal): void
    {
        Config::set('database.connections.tenant', array_merge(
            config('tenancy.connection'),
            ['database' => $portal->database_name]
        ));

        DB::purge('tenant');
    }

    /** Connection to the DB server itself (no database selected) – used to create/drop tenant DBs. */
    public static function server()
    {
        Config::set('database.connections.tenant_server', array_merge(
            config('tenancy.connection'),
            ['database' => null]
        ));

        DB::purge('tenant_server');

        return DB::connection('tenant_server');
    }
}
