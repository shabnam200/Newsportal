<?php

namespace App\Services;

use App\Models\Portal;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class TenantProvisioner
{
    /**
     * Create the tenant database for a portal, import the schema and seed app_config.
     * Safe to call again: it only fills in what is missing.
     */
    public function provision(Portal $portal): void
    {
        try {
            $dbName = $portal->database_name ?: $this->databaseNameFor($portal);

            // dbName is built from a prefix + integer id, but we still restrict it to be safe.
            if (!preg_match('/^[A-Za-z0-9_]{1,64}$/', $dbName)) {
                throw new \RuntimeException("Invalid tenant database name: {$dbName}");
            }

            TenantManager::server()->statement(
                "CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci"
            );

            $portal->database_name = $dbName;
            TenantManager::connect($portal);

            $tenant = DB::connection('tenant');

            $this->ensureSchema($tenant, $portal);
            $this->runMigrations($tenant);
            $this->createViews($tenant);
            app(config('tenancy.seeder'))->seed($tenant, $portal);

            $portal->provisioned_at = now();
            $portal->provision_error = null;
            $portal->save();
        } catch (Throwable $e) {
            Log::error('Tenant provisioning failed for portal ' . $portal->id . ': ' . $e->getMessage());
            $portal->provision_error = $e->getMessage();
            $portal->save();
            throw $e;
        }
    }

    private function databaseNameFor(Portal $portal): string
    {
        $prefix = (string) config('tenancy.database_prefix');
        $suffix = '_' . $portal->id;
        $slugLength = 64 - strlen($prefix) - strlen($suffix);

        if ($slugLength < 1) {
            throw new \RuntimeException('The tenant database prefix is too long.');
        }

        $slug = Str::slug($portal->portal_name, '_');
        $slug = substr($slug !== '' ? $slug : 'portal', 0, $slugLength);

        return $prefix . $slug . $suffix;
    }

    /** Drop the tenant database (used when admin deletes a portal). */
    public function destroy(Portal $portal): void
    {
        if ($portal->database_name && preg_match('/^[A-Za-z0-9_]{1,64}$/', $portal->database_name)) {
            TenantManager::server()->statement("DROP DATABASE IF EXISTS `{$portal->database_name}`");
        }
    }

    /** True when a database with this name exists on the tenant server. */
    public function databaseExists(string $name): bool
    {
        return !empty(TenantManager::server()->select(
            'SELECT SCHEMA_NAME FROM information_schema.SCHEMATA WHERE SCHEMA_NAME = ?',
            [$name]
        ));
    }

    /**
     * Make sure the tenant DB holds the full schema.
     *  - complete (marker table says so)        -> nothing to do
     *  - complete but created by the old code   -> just write the marker
     *  - empty                                  -> import (and clean up again if the import fails)
     *  - half-imported and holds no data        -> wipe and import again
     *  - half-imported and holds data           -> stop, a human has to look at it
     */
    private function ensureSchema($connection, Portal $portal): void
    {
        if ($this->tableExists($connection, 'tenant_meta')) {
            $owner = $connection->table('tenant_meta')->where('meta_key', 'installation_id')->value('meta_value');

            if ($owner !== null && $owner !== config('tenancy.installation_id')) {
                throw new \RuntimeException(
                    "Database {$portal->database_name} belongs to another installation. "
                    . 'Use a different TENANT_DB_PREFIX for this environment.'
                );
            }

            if ($owner !== null && $connection->table('tenant_meta')->where('meta_key', 'schema_imported')->exists()) {
                return;
            }
        }

        $existing = $this->baseTables($connection);

        if (!empty($existing)) {
            $missing = array_diff($this->expectedTables(), $existing);

            if (empty($missing)) {
                $this->writeMeta($connection, $portal);
                return;
            }

            if ($this->hasData($connection, $existing)) {
                throw new \RuntimeException(
                    'Tenant database is only partly set up (missing: ' . implode(', ', array_slice($missing, 0, 5))
                    . (count($missing) > 5 ? ', ...' : '') . ') and already holds data, so it was not touched. Fix it manually.'
                );
            }

            $this->dropEverything($connection);
        }

        try {
            $this->importSchema($connection);
            $this->writeMeta($connection, $portal);
        } catch (Throwable $e) {
            // A failed import must not leave a half-built database that later looks "done".
            try {
                $this->dropEverything($connection);
            } catch (Throwable $ignored) {
            }
            throw $e;
        }
    }

    private function importSchema($connection): void
    {
        $sql = file_get_contents(config('tenancy.schema_file'));
        $withData = config('tenancy.import_sample_data');

        foreach ($this->splitStatements($sql) as $statement) {
            $head = strtoupper(substr(ltrim($statement), 0, 20));

            if (str_starts_with($head, 'START TRANSACTION') || str_starts_with($head, 'COMMIT')) {
                continue;
            }
            if (!$withData && str_starts_with($head, 'INSERT')) {
                continue;
            }

            $connection->unprepared($statement);
        }
    }

    /** Written last, so its existence means the import finished. */
    private function writeMeta($connection, Portal $portal): void
    {
        $connection->unprepared(
            'CREATE TABLE IF NOT EXISTS `tenant_meta` ('
            . '`meta_key` varchar(64) NOT NULL, `meta_value` varchar(255) DEFAULT NULL, PRIMARY KEY (`meta_key`)'
            . ') ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
        );

        foreach ([
            'installation_id' => config('tenancy.installation_id'),
            'portal_id' => (string) $portal->id,
            'schema_imported' => now()->toDateTimeString(),
        ] as $key => $value) {
            $connection->table('tenant_meta')->updateOrInsert(['meta_key' => $key], ['meta_value' => $value]);
        }
    }

    /** Apply database/tenant/migrations/*.sql files this tenant has not seen yet. */
    public function runMigrations($connection): void
    {
        $connection->unprepared(
            'CREATE TABLE IF NOT EXISTS `tenant_migrations` ('
            . '`migration` varchar(190) NOT NULL, `applied_at` datetime NOT NULL, PRIMARY KEY (`migration`)'
            . ') ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
        );

        $files = glob(rtrim(config('tenancy.migrations_path'), '/\\') . '/*.sql') ?: [];
        sort($files);

        $done = $connection->table('tenant_migrations')->pluck('migration')->all();

        foreach ($files as $file) {
            $name = basename($file);
            if (in_array($name, $done, true)) {
                continue;
            }

            foreach ($this->splitStatements(file_get_contents($file)) as $statement) {
                $connection->unprepared($statement);
            }

            $connection->table('tenant_migrations')->insert(['migration' => $name, 'applied_at' => now()]);
        }
    }

    private function tableExists($connection, string $table): bool
    {
        return in_array($table, $this->baseTables($connection), true);
    }

    /** @return string[] */
    private function baseTables($connection): array
    {
        return array_map(
            fn ($row) => (string) array_values((array) $row)[0],
            $connection->select("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'")
        );
    }

    /** Table names the schema file creates. */
    private function expectedTables(): array
    {
        preg_match_all('/CREATE TABLE\s+`([^`]+)`/i', file_get_contents(config('tenancy.schema_file')), $m);

        return array_values(array_unique($m[1]));
    }

    /** Any rows besides the seeded app_config / marker tables? */
    private function hasData($connection, array $tables): bool
    {
        foreach ($tables as $table) {
            if (in_array($table, ['app_config', 'tenant_meta', 'tenant_migrations'], true)) {
                continue;
            }
            if ($connection->table($table)->exists()) {
                return true;
            }
        }

        return false;
    }

    private function dropEverything($connection): void
    {
        $connection->unprepared('SET FOREIGN_KEY_CHECKS=0');

        try {
            foreach ($connection->select("SHOW FULL TABLES WHERE Table_type = 'VIEW'") as $row) {
                $connection->unprepared('DROP VIEW IF EXISTS `' . array_values((array) $row)[0] . '`');
            }
            foreach ($this->baseTables($connection) as $table) {
                $connection->unprepared("DROP TABLE IF EXISTS `{$table}`");
            }
        } finally {
            $connection->unprepared('SET FOREIGN_KEY_CHECKS=1');
        }
    }

    /** Create/refresh the tenant views (database/tenant/views.sql). Safe to run repeatedly. */
    public function createViews($connection): void
    {
        $file = config('tenancy.views_file');

        if (!is_file($file)) {
            return;
        }

        foreach ($this->splitStatements(file_get_contents($file)) as $statement) {
            $connection->unprepared($statement);
        }
    }

    /** Splits a phpMyAdmin-style dump into statements (aware of quoted strings and comments). */
    private function splitStatements(string $sql): array
    {
        $statements = [];
        $buf = '';
        $inStr = false;
        $quote = '';
        $len = strlen($sql);

        for ($i = 0; $i < $len; $i++) {
            $c = $sql[$i];

            if ($inStr) {
                $buf .= $c;
                if ($c === '\\' && $i + 1 < $len) {
                    $buf .= $sql[++$i];
                } elseif ($c === $quote) {
                    if ($i + 1 < $len && $sql[$i + 1] === $quote) {
                        $buf .= $sql[++$i];
                    } else {
                        $inStr = false;
                    }
                }
                continue;
            }

            // -- line comment
            if ($c === '-' && ($sql[$i + 1] ?? '') === '-' && in_array($sql[$i + 2] ?? "\n", [' ', "\n", "\r", "\t"], true)) {
                $nl = strpos($sql, "\n", $i);
                $i = $nl === false ? $len : $nl;
                continue;
            }

            // /* block comment */  (keep executable /*! ... */ ones)
            if ($c === '/' && ($sql[$i + 1] ?? '') === '*') {
                $end = strpos($sql, '*/', $i + 2);
                $end = $end === false ? $len - 1 : $end + 1;
                if (($sql[$i + 2] ?? '') === '!') {
                    $buf .= substr($sql, $i, $end - $i + 1);
                }
                $i = $end;
                continue;
            }

            if ($c === "'" || $c === '"') {
                $inStr = true;
                $quote = $c;
                $buf .= $c;
                continue;
            }

            if ($c === ';') {
                if (trim($buf) !== '') {
                    $statements[] = trim($buf);
                }
                $buf = '';
                continue;
            }

            $buf .= $c;
        }

        if (trim($buf) !== '') {
            $statements[] = trim($buf);
        }

        return $statements;
    }
}
