<?php

namespace App\Http\Controllers;

use App\Models\Recommendation;
use App\Models\RecommendationConversion;
use App\Models\Shop;
use Illuminate\Http\Request;

class RecommendationsController extends Controller
{
    public function showRecommendations(Request $request)
    {
        $shopDomain = $request->query('shop');
        $search     = $request->query('search'); // <-- added
        $shop       = Shop::where('shopify_domain', $shopDomain)->first();

        $recs     = [];
        $products = [];

        if ($shop) {
            $products = $shop->products()->get()->keyBy('shopify_product_id');

            // Filter product/recommendation_product IDs by title
            $filteredProductIds = [];
            if ($search) {
                $filteredProductIds = $products->filter(function ($product) use ($search) {
                    return stripos($product->title, $search) !== false;
                })->keys()->toArray();
            }

            $recsQuery = Recommendation::where('shop_id', $shop->id)
                ->with(['shop']);

            if ($search && !empty($filteredProductIds)) {
                $recsQuery->where(function ($query) use ($filteredProductIds) {
                    $query->whereIn('product_id', $filteredProductIds)
                        ->orWhereIn('recommended_product_id', $filteredProductIds);
                });
            }

            $recs = $recsQuery->paginate(10)->withQueryString(); // keep `search` in pagination links
        }

        $conversions = [];
        if ($shop) {
            $conversions = RecommendationConversion::where('shop_id', $shop->id)
                ->get()
                ->groupBy('recommendation_id');
        }

        return view('recommendations.index', compact('recs', 'products', 'shop', 'conversions', 'search'));
    }

    public function createRecommendation(Request $request)
    {
        $shopDomain = $request->query('shop');
        $shop       = Shop::where('shopify_domain', $shopDomain)->firstOrFail();

        $request->validate([
            'product_id'             => 'required|numeric',
            'recommended_product_id' => 'required|numeric|different:product_id',
        ]);

        Recommendation::create([
            'shop_id'                => $shop->id,
            'product_id'             => $request->input('product_id'),
            'recommended_product_id' => $request->input('recommended_product_id'),
            'algo'                   => 'manual',
            'meta'                   => [],
        ]);

        return redirect()->route('recommendations.index', [
            'shop' => $shopDomain,
            'success' => 'Recommendation added!'
        ])->with('success', 'Recommendation added!');
    }

    public function delete($id, Request $request)
    {
        $rec = Recommendation::findOrFail($id);
        $shopDomain = $request->query('shop');
        $rec->delete();
        return redirect()->route('recommendations.index', ['shop' => $shopDomain])
            ->with('success', 'Recommendation deleted!');
    }
}
