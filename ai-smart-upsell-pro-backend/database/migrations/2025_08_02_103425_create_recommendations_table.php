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
        Schema::create('recommendations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained('shops')->onDelete('cascade');
            $table->bigInteger('product_id'); // The product being viewed or purchased
            $table->bigInteger('recommended_product_id'); // The product being recommended as an upsell
            $table->string('algo')->nullable(); // 'manual', 'ai', etc
            $table->json('meta')->nullable();   // (Optional) extra data from AI or rules
            $table->boolean('enabled')->default(true);
            $table->timestamps();

            // Index for fast lookup
            $table->index(['shop_id', 'product_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recommendations');
    }
};
