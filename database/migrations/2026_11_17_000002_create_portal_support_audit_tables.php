<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('portal_support_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_id')->nullable()->constrained()->nullOnDelete();
            $table->string('admin_name', 100);
            $table->foreignId('portal_id')->nullable()->constrained()->nullOnDelete();
            $table->string('portal_name', 100);
            $table->timestamp('started_at');
            $table->timestamp('last_activity_at')->nullable()->index();
            $table->timestamp('ended_at')->nullable()->index();
            $table->index(['portal_id', 'started_at']);
        });

        Schema::create('portal_support_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('support_session_id')
                ->constrained('portal_support_sessions')
                ->cascadeOnDelete();
            $table->string('action', 20);
            $table->string('path', 500)->nullable();
            $table->timestamp('created_at')->useCurrent()->index();
            $table->index(['support_session_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('portal_support_events');
        Schema::dropIfExists('portal_support_sessions');
    }
};
