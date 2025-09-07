<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Shop;
use App\Models\Recommendation;
use App\Models\RecommendationConversion;

class HomeController extends Controller
{
    public function index(Request $request)
    {
        $shopDomain = $request->query('shop');
        $shop = Shop::where('shopify_domain', $shopDomain)->firstOrFail();

        $recommendations = Recommendation::where('shop_id', $shop->id)->get();
        $products = $shop->products()->pluck('title', 'shopify_product_id')->toArray();
        $conversions = RecommendationConversion::whereIn('recommendation_id', $recommendations->pluck('id'))->get()->groupBy('recommendation_id');

        return view('home', compact('shop', 'recommendations', 'products', 'conversions'));
    }
}
