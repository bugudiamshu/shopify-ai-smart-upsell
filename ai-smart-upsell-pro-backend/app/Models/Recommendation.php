<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Recommendation extends Model
{
    protected $fillable = [
        'shop_id', 'product_id', 'recommended_product_id', 'algo', 'meta'
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }

    public function conversions()
    {
        return $this->hasMany(RecommendationConversion::class);
    }

    public function product()
    {
        // Assuming 'product_id' stores shopify_product_id and your products table uses 'shopify_product_id'
        return $this->belongsTo(Product::class, 'product_id', 'shopify_product_id');
    }

    // Relationship to the recommended product upsell
    public function recommendedProduct()
    {
        return $this->belongsTo(Product::class, 'recommended_product_id', 'shopify_product_id');
    }

}
