<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Shop;
use App\Models\Recommendation;
use App\Models\Product;
use App\Models\RecommendationConversion;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    public function orderCreate(Request $request)
    {
        Log::info(json_encode($request->all(), JSON_PRETTY_PRINT));

        // Shopify will send JSON, automatically parsed!
        $shopDomain = $request->header('X-Shopify-Shop-Domain');
        $shop = Shop::where('shopify_domain', $shopDomain)->first();

        if (!$shop) {
            return response('Shop not found', 404);
        }

        // Assume you want to track EVERY product that was in a recommendation
        $lineProductIds = collect($request->input('line_items', []))->pluck('product_id')->toArray();

        if (!empty($lineProductIds)) {
            $recs = Recommendation::where('shop_id', $shop->id)
                ->whereIn('recommended_product_id', $lineProductIds)
                ->get();

            foreach ($recs as $rec) {
                RecommendationConversion::create([
                    'shop_id'           => $shop->id,
                    'order_id'          => $request->input('id'),
                    'recommendation_id' => $rec->id,
                    'product_id'        => $rec->recommended_product_id,
                ]);
            }
        }

        // Store all included product pairs for future AI/ML
        $productIds = $lineProductIds;
        foreach ($productIds as $i => $a) {
            foreach ($productIds as $j => $b) {
                if ($i !== $j) {
                    \App\Models\ProductOrderPair::updateOrCreate([
                        'shop_id' => $shop->id,
                        'order_id' => $request->input('id'),
                        'product_id' => $a,
                        'also_bought_product_id' => $b,
                    ], [
                        'pair_count' => DB::raw('pair_count + 1')
                    ]);
                }
            }
        }

        return response('ok', 200);
    }
}
