<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('shops', function (Blueprint $table) {
            $table->id();
            $table->string('shopify_domain')->unique();      // e.g., nitulabs.myshopify.com
            $table->string('shopify_token');                // OAuth access token
            $table->string('name')->nullable();             // (Optional) store name
            $table->string('email')->nullable();            // (Optional) store email
            $table->string('currency')->default('USD');
            $table->string('money_format')->nullable();
            $table->string('plan_name')->nullable();        // (Optional) plan info from Shopify API
            $table->boolean('grandfathered')->default(false); // (Optional) for billing logic
            $table->boolean('freemium')->default(false);      // (Optional) for billing logic
            $table->unsignedBigInteger('installed_at')->nullable(); // timestamp of install (optional)
            $table->softDeletes();                          // allows soft-delete (uninstalled shops)
            $table->timestamps();                           // created_at & updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shops');
    }
};
