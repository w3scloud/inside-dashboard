<?php

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
        // Add missing indexes and fix constraints
        Schema::table('dashboards', function (Blueprint $table) {
            // Add index for faster queries
            $table->index(['store_id', 'is_default']);
            $table->index('last_viewed_at');

            // Ensure only one default dashboard per store
            $table->unique(['store_id', 'is_default']);
        });

        Schema::table('widgets', function (Blueprint $table) {
            // Add better indexing
            $table->index(['dashboard_id', 'type']);
            $table->index('type');
            $table->index('data_source');

            // Add missing fields that might be needed
            if (! Schema::hasColumn('widgets', 'is_active')) {
                $table->boolean('is_active')->default(true);
            }

            if (! Schema::hasColumn('widgets', 'order')) {
                $table->integer('order')->default(0);
            }
        });

        // Create widget_templates table for predefined widget types
        if (! Schema::hasTable('widget_templates')) {
            Schema::create('widget_templates', function (Blueprint $table) {
                $table->id();
                $table->string('type')->unique();
                $table->string('name');
                $table->text('description')->nullable();
                $table->string('icon')->nullable();
                $table->json('default_size'); // {w: 4, h: 4}
                $table->json('min_size')->nullable(); // {w: 2, h: 2}
                $table->json('max_size')->nullable(); // {w: 12, h: 8}
                $table->json('default_config')->nullable();
                $table->json('available_data_sources');
                $table->json('supported_chart_types')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dashboards', function (Blueprint $table) {
            $table->dropIndex(['store_id', 'is_default']);
            $table->dropIndex(['last_viewed_at']);
            $table->dropUnique(['store_id', 'is_default']);
        });

        Schema::table('widgets', function (Blueprint $table) {
            $table->dropIndex(['dashboard_id', 'type']);
            $table->dropIndex(['type']);
            $table->dropIndex(['data_source']);

            if (Schema::hasColumn('widgets', 'is_active')) {
                $table->dropColumn('is_active');
            }
            if (Schema::hasColumn('widgets', 'order')) {
                $table->dropColumn('order');
            }
        });

        Schema::dropIfExists('widget_templates');
    }
};
