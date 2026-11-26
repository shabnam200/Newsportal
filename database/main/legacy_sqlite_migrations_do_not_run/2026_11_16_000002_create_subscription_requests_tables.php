<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // A portal asks for its grace period to run until a later date.
        Schema::create('grace_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('portal_id')->constrained()->cascadeOnDelete();
            $table->date('until_date');                       // date the portal wants the grace to end
            $table->string('reason', 500)->nullable();
            $table->string('status', 20)->default('pending'); // pending | approved | rejected
            $table->string('admin_note', 255)->nullable();
            $table->date('approved_until')->nullable();       // what the admin actually granted
            $table->timestamp('decided_at')->nullable();
            $table->unsignedBigInteger('decided_by')->nullable(); // admin id
            $table->timestamps();

            $table->index(['portal_id', 'status']);
        });

        // A portal says "I paid / I want to renew". Admin checks the money, then approves,
        // which records the real Payment and extends the subscription.
        Schema::create('purchase_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('portal_id')->constrained()->cascadeOnDelete();
            $table->foreignId('package_id')->constrained();
            $table->unsignedInteger('months');
            $table->decimal('amount', 10, 2);                 // what the portal was asked to pay
            $table->string('method', 20);
            $table->string('trx_id', 100)->nullable();
            $table->string('note', 255)->nullable();
            $table->string('status', 20)->default('pending'); // pending | approved | rejected
            $table->string('admin_note', 255)->nullable();
            $table->unsignedBigInteger('payment_id')->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->unsignedBigInteger('decided_by')->nullable();
            $table->timestamps();

            $table->index(['portal_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_requests');
        Schema::dropIfExists('grace_requests');
    }
};
