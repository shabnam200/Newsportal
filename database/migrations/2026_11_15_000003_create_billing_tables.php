<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('packages', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('slug', 50)->unique();
            $table->decimal('price', 10, 2)->default(0);
            $table->unsignedInteger('duration_days');
            $table->unsignedInteger('grace_days')->default(0);
            $table->boolean('is_trial')->default(false);
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('portal_id')->constrained()->cascadeOnDelete();
            $table->foreignId('package_id')->constrained();
            $table->date('starts_at');
            $table->date('ends_at');            // last day (inclusive) the portal can be used
            $table->unsignedInteger('grace_days')->default(0); // snapshot from package
            $table->decimal('price', 10, 2)->default(0);       // snapshot from package
            $table->timestamps();
            $table->index(['portal_id', 'ends_at']);
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('portal_id')->constrained()->cascadeOnDelete();
            $table->foreignId('package_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('subscription_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('amount', 10, 2);
            $table->string('method', 20);       // cash | bkash | nagad | rocket | bank | other
            $table->string('trx_id', 100)->nullable();
            $table->string('note', 255)->nullable();
            $table->timestamp('paid_at');
            $table->unsignedBigInteger('recorded_by')->nullable(); // admin id
            $table->timestamps();
        });

        Schema::create('portal_charges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('portal_id')->constrained()->cascadeOnDelete();
            $table->string('purpose', 150);
            $table->decimal('amount', 10, 2);
            $table->string('type', 20);         // one_time | cycle
            $table->timestamp('paid_at')->nullable(); // only used for one_time
            $table->timestamps();
        });

        $now = now();
        DB::table('packages')->insert([
            ['name' => 'Trial Package', 'slug' => 'trial', 'price' => 0, 'duration_days' => 7,
             'grace_days' => 0, 'is_trial' => true, 'active' => true, 'created_at' => $now, 'updated_at' => $now],
            ['name' => 'Package-1', 'slug' => 'package-1', 'price' => 2000, 'duration_days' => 30,
             'grace_days' => 5, 'is_trial' => false, 'active' => true, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('portal_charges');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('subscriptions');
        Schema::dropIfExists('packages');
    }
};
