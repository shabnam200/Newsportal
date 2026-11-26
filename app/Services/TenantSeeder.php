<?php

namespace App\Services;

use App\Models\Portal;

/**
 * Fills a tenant database with its first rows. Safe to run again (every step checks first), so it is
 * also run by `php artisan tenants:migrate` and by "Create / repair tenant database".
 * To reuse this code for a different system, write your own class with a seed() method
 * and set it in config/tenancy.php ('seeder').
 */
class TenantSeeder
{
    public function seed($connection, Portal $portal): void
    {
        $this->seedAppConfig($connection, $portal);
        $this->seedEditor($connection, $portal);
    }

    private function seedAppConfig($connection, Portal $portal): void
    {
        if ($connection->table('app_config')->exists()) {
            return;
        }

        $connection->table('app_config')->insert([
            'id' => 1,
            'time_zone' => config('tenancy.default_time_zone', 'Asia/Dhaka'),
            'portal_name' => mb_substr($portal->portal_name, 0, 50),
            'email' => $portal->editor_email,
            'contact' => $portal->editor_mobile,
            'last_modifier' => 0,
        ]);
    }

    /** The portal's editor is the first user of the tenant `users` table (matched by mobile number). */
    private function seedEditor($connection, Portal $portal): void
    {
        $mobile = (string) $portal->editor_mobile;

        if ($mobile === '' || $connection->table('users')->where('user_mobile', $mobile)->exists()) {
            return;
        }

        $name = trim((string) $portal->editor_name) ?: $portal->portal_name;

        $connection->table('users')->insert([
            'username' => mb_substr($mobile, 0, 50),
            'user_full_name' => mb_substr($name, 0, 150),
            'user_address' => '',
            'user_type' => 1,
            'email' => (string) $portal->editor_email,
            'user_mobile' => $mobile,
            'password' => (string) $portal->password,
            'activated' => 1,
            'banned' => 0,
            'last_ip' => '',
            'created' => now(),
        ]);
    }
}
