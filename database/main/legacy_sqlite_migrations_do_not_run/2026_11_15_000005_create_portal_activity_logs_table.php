<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('portal_activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('portal_id')->constrained()->cascadeOnDelete();
            $table->string('event', 20)->default('request'); // request | login | login_blocked
            $table->string('method', 10)->nullable();
            $table->string('path', 500)->nullable();          // path only, no query string / body
            $table->string('route_name', 100)->nullable();
            $table->unsignedSmallInteger('status')->nullable();
            $table->string('ip', 45)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->unsignedInteger('duration_ms')->default(0);
            $table->unsignedInteger('db_queries')->default(0); // queries run on the tenant database
            $table->unsignedInteger('db_writes')->default(0);  // insert/update/delete/ddl on tenant database
            $table->timestamp('created_at')->nullable()->index();

            $table->index(['portal_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('portal_activity_logs');
    }
};
