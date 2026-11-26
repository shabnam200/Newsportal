<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('packages', function (Blueprint $table) {
            // 1 = monthly plan, 12 = yearly plan (paid month by month). price = charge per month.
            $table->unsignedInteger('term_months')->default(1);
        });

        Schema::table('subscriptions', function (Blueprint $table) {
            $table->unsignedInteger('term_months')->default(1);
            $table->unsignedInteger('paid_months')->default(1);
            $table->unsignedInteger('period_days')->default(30);
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->unsignedInteger('months')->default(1);
        });

        // old subscription rows: copy the real cycle length from their package
        DB::statement('UPDATE subscriptions SET period_days = (SELECT duration_days FROM packages WHERE packages.id = subscriptions.package_id)');
    }

    public function down(): void
    {
        Schema::table('payments', fn (Blueprint $t) => $t->dropColumn('months'));
        Schema::table('subscriptions', fn (Blueprint $t) => $t->dropColumn(['term_months', 'paid_months', 'period_days']));
        Schema::table('packages', fn (Blueprint $t) => $t->dropColumn('term_months'));
    }
};
