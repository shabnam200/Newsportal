<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('portals', function (Blueprint $table) {
            // nullable so portals created before passwords existed keep working;
            // they set a password on their next login.
            $table->string('password')->nullable()->after('editor_mobile');
        });
    }

    public function down(): void
    {
        Schema::table('portals', function (Blueprint $table) {
            $table->dropColumn('password');
        });
    }
};
