<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Recommendation;
use App\Models\Shop;
use App\Models\UpsellClick;
use App\Models\UpsellImpression;
use Illuminate\Http\Request;

class UpsellController extends Controller
{
    public function fetch(Request $request)
    {
        $shopDomain = $request->query('shop');
        $productId  = $request->query('product_id');
        $visitorId  = $request->query('visitor_id', null);

        $shop = Shop::where('shopify_domain', $shopDomain)->first();
        if (!$shop || !$productId) {
            return response()->json(['error' => 'Invalid shop or product'], 400);
        }

        $recommendations = Recommendation::where('shop_id', $shop->id)
            ->where('product_id', $productId)
            ->where('enabled', true)
            ->with('recommendedProduct')
            ->get()
            ->map(fn($rec) => [
                'id'         => $rec->id,
                'upsell_id'  => $rec->recommended_product_id,
                'title'      => $rec->recommendedProduct->title ?? '',
                'image'      => $rec->recommendedProduct->images[0]['src'] ?? '',
                'price'      => $rec->recommendedProduct->price ?? '',
                'algo'       => $rec->algo,
                'handle'     => $rec->recommendedProduct->handle,
                'variant_id' => $rec->recommendedProduct->variant_id ?? null,
            ]);

        return response()->json([
            'money_format' => $shop->money_format,
            'upsells'      => $recommendations,
        ]);
    }

    public function recordClick(Request $request)
    {
        $request->validate([
            'shop'              => 'required|string',
            'product_id'        => 'required|integer',
            'recommendation_id' => 'required|string',
            'visitor_id'        => 'nullable|string',
        ]);

        $shop = Shop::where('shopify_domain', $request->shop)->first();
        if (!$shop) {
            return response()->json(['error' => 'Shop not found'], 404);
        }

        UpsellClick::create([
            'shop_id'           => $shop->id,
            'product_id'        => $request->product_id,
            'recommendation_id' => $request->recommendation_id,
            'visitor_id'        => $request->visitor_id,
        ]);

        return response()->json(['message' => 'Upsell click recorded successfully']);
    }

    public function recordImpression(Request $request)
    {
        $request->validate([
            'shop'       => 'required|string',
            'product_id' => 'required|integer',
            'visitor_id' => 'nullable|string',
        ]);

        $shop = Shop::where('shopify_domain', $request->shop)->first();
        if (!$shop) {
            return response()->json(['error' => 'Shop not found'], 404);
        }

        UpsellImpression::updateOrCreate(
            [
                'shop_id'    => $shop->id,
                'product_id' => $request->product_id,
                'visitor_id' => $request->visitor_id,
            ],
            ['created_at' => now()]
        );

        return response()->json(['message' => 'Impression recorded']);
    }
}
