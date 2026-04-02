<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commerce_orders', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('number')->unique();
            $table->foreignUuid('cart_id')
                ->nullable()
                ->constrained('commerce_carts')
                ->nullOnDelete();
            $table->string('actor_type');
            $table->string('actor_id');
            $table->boolean('actor_authenticated')->default(false);
            $table->string('status')->index();
            $table->string('currency', 3)->nullable();
            $table->unsignedInteger('item_count')->default(0);
            $table->unsignedInteger('items_quantity')->default(0);
            $table->decimal('subtotal_amount', 12, 2)->default(0);
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->json('customer_snapshot')->nullable();
            $table->json('meta')->nullable();
            $table->timestamp('placed_at')->nullable()->index();
            $table->timestamp('cancelled_at')->nullable()->index();
            $table->timestamps();

            $table->index(['actor_type', 'actor_id', 'status'], 'commerce_orders_actor_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commerce_orders');
    }
};
