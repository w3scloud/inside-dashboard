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
        Schema::create('widgets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dashboard_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('type');
            $table->string('chart_type')->nullable();
            $table->string('data_source');
            $table->json('size');
            $table->json('position');
            $table->json('config')->nullable();
            $table->json('filters')->nullable();
            $table->json('settings')->nullable();
            $table->timestamp('last_refreshed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('widgets');
    }
};
