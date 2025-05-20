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
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('type');
            $table->json('config');
            $table->boolean('schedule_enabled')->default(false);
            $table->string('schedule_frequency')->nullable();
            $table->string('schedule_time')->nullable();
            $table->integer('schedule_day')->nullable();
            $table->json('schedule_recipients')->nullable();
            $table->timestamp('last_generated_at')->nullable();
            $table->string('output_format')->default('pdf');
            $table->json('filters')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};
