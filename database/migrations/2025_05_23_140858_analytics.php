<?php

// database/migrations/2025_05_23_130000_create_analytics_tables.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Daily sales summary table
        Schema::create('analytics_daily_sales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->decimal('total_sales', 15, 2)->default(0);
            $table->integer('order_count')->default(0);
            $table->timestamps();

            $table->unique(['store_id', 'date']);
            $table->index(['store_id', 'date']);
        });

        // Product sales analytics table
        Schema::create('analytics_product_sales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->string('product_id');
            $table->date('date');
            $table->decimal('revenue', 15, 2)->default(0);
            $table->integer('quantity')->default(0);
            $table->timestamps();

            $table->unique(['store_id', 'product_id', 'date']);
            $table->index(['store_id', 'date']);
            $table->index(['store_id', 'revenue']);
        });

        // Customer metrics table
        Schema::create('analytics_customer_metrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->string('customer_id');
            $table->decimal('total_spent', 15, 2)->default(0);
            $table->integer('orders_count')->default(0);
            $table->timestamps();

            $table->unique(['store_id', 'customer_id']);
            $table->index(['store_id', 'total_spent']);
        });

        // Inventory levels table
        Schema::create('analytics_inventory_levels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->string('inventory_item_id');
            $table->integer('available')->default(0);
            $table->timestamps();

            $table->unique(['store_id', 'inventory_item_id']);
            $table->index(['store_id', 'available']);
        });

        // Hourly sales breakdown table
        Schema::create('analytics_hourly_sales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->tinyInteger('hour'); // 0-23
            $table->decimal('total_sales', 15, 2)->default(0);
            $table->integer('order_count')->default(0);
            $table->timestamps();

            $table->unique(['store_id', 'date', 'hour']);
            $table->index(['store_id', 'date']);
        });

        // Real-time events log table
        Schema::create('analytics_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->string('event_type'); // order_created, product_updated, etc.
            $table->string('entity_id'); // order_id, product_id, etc.
            $table->json('event_data');
            $table->decimal('value', 15, 2)->nullable(); // monetary value if applicable
            $table->timestamp('occurred_at');
            $table->timestamps();

            $table->index(['store_id', 'event_type']);
            $table->index(['store_id', 'occurred_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('analytics_events');
        Schema::dropIfExists('analytics_hourly_sales');
        Schema::dropIfExists('analytics_inventory_levels');
        Schema::dropIfExists('analytics_customer_metrics');
        Schema::dropIfExists('analytics_product_sales');
        Schema::dropIfExists('analytics_daily_sales');
    }
};
