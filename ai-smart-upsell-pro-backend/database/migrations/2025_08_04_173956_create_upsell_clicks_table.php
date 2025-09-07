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
        Schema::create('upsell_clicks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained('shops')->onDelete('cascade');
            $table->bigInteger('product_id'); // The original product
            $table->foreignId('recommendation_id')->constrained('recommendations')->onDelete('cascade'); // The clicked upsell
            $table->string('visitor_id')->nullable();
            $table->timestamps();

            $table->index(['shop_id', 'product_id']);
            $table->index(['shop_id', 'recommendation_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('upsell_clicks');
    }
};
