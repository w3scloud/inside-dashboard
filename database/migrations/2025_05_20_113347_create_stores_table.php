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
        Schema::create('stores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('shop_domain')->unique();
            $table->string('access_token')->nullable();
            $table->json('scopes')->nullable();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('plan_name')->nullable();
            $table->string('plan_display_name')->nullable();
            $table->string('shop_owner')->nullable();
            $table->string('myshopify_domain')->nullable();
            $table->string('money_format')->nullable();
            $table->string('currency')->nullable();
            $table->string('timezone')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('installed_at')->nullable();
            $table->timestamp('uninstalled_at')->nullable();
            $table->string('access_scopes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stores');
    }
};
