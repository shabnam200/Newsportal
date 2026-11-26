<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const PORTAL_TABLES = [
        'subscriptions' => false,
        'payments' => false,
        'portal_charges' => false,
        'grace_requests' => false,
        'purchase_requests' => false,
        'portal_activity_logs' => false,
        'portal_support_sessions' => true,
    ];

    public function up(): void
    {
        if (!Schema::hasTable('users')) {
            throw new RuntimeException('The existing users table is required before portal accounts can be moved.');
        }

        if (!Schema::hasTable('portals')) {
            if (Schema::hasColumn('users', 'account_kind')) {
                return;
            }

            throw new RuntimeException('The source portals table is missing; no portal data was moved.');
        }

        $portals = DB::table('portals')->orderBy('id')->get();
        $this->assertPortalIdsAvailable($portals);
        $this->addPortalColumns();
        $this->ensurePortalMobileIndex();
        $this->dropPortalForeignKeys();

        foreach ($portals as $portal) {
            $user = DB::table('users')->where('id', $portal->id)->first();
            $data = [
                'username' => 'portal_' . $portal->id,
                'user_full_name' => $portal->editor_name ?? '',
                'user_address' => '',
                'user_type' => 0,
                'email' => $portal->editor_email ?? '',
                'user_mobile' => $portal->editor_mobile,
                'activated' => 0,
                'banned' => 1,
                'last_ip' => '0.0.0.0',
                'last_login' => $portal->last_login_at,
                'created' => $portal->created_at,
                'modified' => $portal->updated_at ?? now(),
                'account_kind' => 'portal',
                'portal_name' => $portal->portal_name,
                'portal_reg_no' => $portal->portal_reg_no,
                'portal_password' => $portal->password,
                'database_name' => $portal->database_name,
                'status' => $portal->status,
                'provisioned_at' => $portal->provisioned_at,
                'provision_error' => $portal->provision_error,
            ];

            if ($user) {
                if ($user->account_kind !== 'portal' || $user->user_mobile !== $portal->editor_mobile) {
                    throw new RuntimeException("User ID {$portal->id} conflicts with a non-portal account.");
                }

                DB::table('users')->where('id', $portal->id)->update($data);
            } else {
                $data['id'] = $portal->id;
                $data['password'] = password_hash(bin2hex(random_bytes(32)), PASSWORD_DEFAULT);
                DB::table('users')->insert($data);
            }
        }

        $this->setPortalForeignKeys('users');
        Schema::drop('portals');
    }

    public function down(): void
    {
        if (!Schema::hasTable('users') || !Schema::hasColumn('users', 'account_kind')) {
            return;
        }

        if (!Schema::hasTable('portals')) {
            Schema::create('portals', function (Blueprint $table) {
                $table->id();
                $table->string('portal_name', 100);
                $table->string('portal_reg_no', 100)->nullable();
                $table->string('editor_name', 100)->nullable();
                $table->string('editor_email', 150)->nullable();
                $table->string('editor_mobile', 20);
                $table->string('password')->nullable();
                $table->string('database_name', 64)->nullable();
                $table->string('status', 20)->default('active');
                $table->timestamp('provisioned_at')->nullable();
                $table->text('provision_error')->nullable();
                $table->timestamp('last_login_at')->nullable();
                $table->timestamps();
                $table->unique(['portal_name', 'editor_mobile']);
            });
        }

        $this->dropPortalForeignKeys();

        foreach (DB::table('users')->where('account_kind', 'portal')->orderBy('id')->get() as $user) {
            DB::table('portals')->updateOrInsert(
                ['id' => $user->id],
                [
                    'portal_name' => $user->portal_name,
                    'portal_reg_no' => $user->portal_reg_no,
                    'editor_name' => $user->user_full_name,
                    'editor_email' => $user->email,
                    'editor_mobile' => $user->user_mobile,
                    'password' => $user->portal_password,
                    'database_name' => $user->database_name,
                    'status' => $user->status,
                    'provisioned_at' => $user->provisioned_at,
                    'provision_error' => $user->provision_error,
                    'last_login_at' => $user->last_login,
                    'created_at' => $user->created,
                    'updated_at' => $user->modified,
                ]
            );
        }

        $this->setPortalForeignKeys('portals');
        DB::table('users')->where('account_kind', 'portal')->delete();

        if ($this->indexExists('users', 'users_account_kind_user_mobile_unique')) {
            DB::statement('ALTER TABLE `users` DROP INDEX `users_account_kind_user_mobile_unique`');
        }

        $columns = [
            'account_kind', 'portal_name', 'portal_reg_no', 'portal_password',
            'database_name', 'status', 'provisioned_at', 'provision_error',
        ];
        $present = array_values(array_filter($columns, fn (string $column) => Schema::hasColumn('users', $column)));
        if ($present) {
            Schema::table('users', fn (Blueprint $table) => $table->dropColumn($present));
        }
    }

    private function addPortalColumns(): void
    {
        $definitions = [
            'account_kind' => ['string', 20],
            'portal_name' => ['string', 100],
            'portal_reg_no' => ['string', 100],
            'portal_password' => ['string', 255],
            'database_name' => ['string', 64],
            'status' => ['string', 20],
            'provisioned_at' => ['timestamp', null],
            'provision_error' => ['text', null],
        ];
        $missing = array_filter($definitions, fn ($definition, $column) => !Schema::hasColumn('users', $column), ARRAY_FILTER_USE_BOTH);

        if (!$missing) {
            return;
        }

        Schema::table('users', function (Blueprint $table) use ($missing): void {
            foreach ($missing as $column => [$type, $length]) {
                if ($type === 'string') {
                    $table->string($column, $length)->nullable();
                } elseif ($type === 'timestamp') {
                    $table->timestamp($column)->nullable();
                } else {
                    $table->text($column)->nullable();
                }
            }
        });
    }

    private function assertPortalIdsAvailable(iterable $portals): void
    {
        $hasAccountKind = Schema::hasColumn('users', 'account_kind');

        foreach ($portals as $portal) {
            $user = DB::table('users')->where('id', $portal->id)->first();
            if (!$user) {
                continue;
            }

            if (!$hasAccountKind || $user->account_kind !== 'portal' || $user->user_mobile !== $portal->editor_mobile) {
                throw new RuntimeException(
                    "Portal ID {$portal->id} collides with an existing users row; no accounts or foreign keys were changed."
                );
            }
        }
    }

    private function ensurePortalMobileIndex(): void
    {
        if (!$this->indexExists('users', 'users_account_kind_user_mobile_unique')) {
            DB::statement('ALTER TABLE `users` ADD UNIQUE INDEX `users_account_kind_user_mobile_unique` (`account_kind`, `user_mobile`)');
        }
    }

    private function dropPortalForeignKeys(): void
    {
        foreach (array_keys(self::PORTAL_TABLES) as $table) {
            $constraints = DB::select(
                'SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?
                 AND REFERENCED_TABLE_NAME IS NOT NULL',
                [$table, 'portal_id']
            );

            foreach ($constraints as $constraint) {
                DB::statement("ALTER TABLE `{$table}` DROP FOREIGN KEY `{$constraint->CONSTRAINT_NAME}`");
            }
        }
    }

    private function setPortalForeignKeys(string $target): void
    {
        foreach (self::PORTAL_TABLES as $table => $nullable) {
            $type = $target === 'users' ? 'INT' : 'BIGINT UNSIGNED';
            $nullability = $nullable ? 'NULL' : 'NOT NULL';
            DB::statement("ALTER TABLE `{$table}` MODIFY `portal_id` {$type} {$nullability}");

            $onDelete = $nullable ? 'SET NULL' : 'CASCADE';
            DB::statement(
                "ALTER TABLE `{$table}` ADD CONSTRAINT `{$table}_portal_id_foreign` "
                . "FOREIGN KEY (`portal_id`) REFERENCES `{$target}` (`id`) ON DELETE {$onDelete}"
            );
        }
    }

    private function indexExists(string $table, string $index): bool
    {
        return (int) DB::table('information_schema.STATISTICS')
            ->where('TABLE_SCHEMA', DB::getDatabaseName())
            ->where('TABLE_NAME', $table)
            ->where('INDEX_NAME', $index)
            ->count() > 0;
    }
};
