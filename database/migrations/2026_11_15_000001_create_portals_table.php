<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('portals', function (Blueprint $table) {
            $table->id();
            $table->string('portal_name', 100);
            $table->string('portal_reg_no', 100)->nullable();
            $table->string('editor_name', 100)->nullable();
            $table->string('editor_email', 150)->nullable();
            $table->string('editor_mobile', 20);
            $table->string('database_name', 64)->nullable();
            $table->string('status', 20)->default('active'); // active | suspended
            $table->timestamp('provisioned_at')->nullable();
            $table->text('provision_error')->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->timestamps();

            $table->unique(['portal_name', 'editor_mobile']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('portals');
    }
};
