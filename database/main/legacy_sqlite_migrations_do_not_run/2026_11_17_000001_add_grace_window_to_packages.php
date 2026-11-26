<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('packages', function (Blueprint $table) {
            // The "expiring soon" banner and the Grace Period button appear when this many days
            // (or fewer) of the paid period are left. Editable per package in Admin > Packages.
            $table->unsignedSmallInteger('grace_window_days')->default(7)->after('grace_days');
        });
    }

    public function down(): void
    {
        Schema::table('packages', function (Blueprint $table) {
            $table->dropColumn('grace_window_days');
        });
    }
};
