<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Recommendation;
use App\Models\Shop;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RecommendationsController extends Controller
{
    public function index(Request $request)
    {
        $shopDomain = $request->query('shop');
        $search     = $request->query('search');

        $shop = Shop::where('shopify_domain', $shopDomain)->first();

        if (!$shop) {
            return response()->json(['error' => 'Invalid shop'], 404);
        }

        $products = $shop->products()->get()->keyBy('shopify_product_id');

        $filteredProductIds = [];
        if ($search) {
            $filteredProductIds = $products->filter(function ($product) use ($search) {
                return stripos($product->title, $search) !== false;
            })->keys()->toArray();
        }

        $recsQuery = Recommendation::where('shop_id', $shop->id)->with('shop');

        if ($search && !empty($filteredProductIds)) {
            $recsQuery->where(function ($query) use ($filteredProductIds) {
                $query->whereIn('product_id', $filteredProductIds)
                    ->orWhereIn('recommended_product_id', $filteredProductIds);
            });
        }

        $recs = $recsQuery->paginate(10);

        // Get IDs of recommendations on current page
        $recIds = $recs->pluck('id')->toArray();

        // Aggregate stats for visible recommendations
        $stats = \DB::table('recommendations as r')
            ->leftJoin('upsell_impressions as i', 'r.product_id', '=', 'i.product_id')
            ->leftJoin('upsell_clicks as c', 'r.id', '=', 'c.recommendation_id')
            ->leftJoin('recommendation_conversions as conv', 'r.id', '=', 'conv.recommendation_id')
            ->whereIn('r.id', $recIds)
            ->select(
                'r.id as recommendation_id',
                \DB::raw('COUNT(DISTINCT i.id) as impressions'),
                \DB::raw('COUNT(DISTINCT c.id) as clicks'),
                \DB::raw('COUNT(DISTINCT conv.id) as conversions')
            )
            ->groupBy('r.id')
            ->get()
            ->keyBy('recommendation_id');

        // Append stats to each recommendation model
        $recs->getCollection()->transform(function ($rec) use ($stats) {
            $stat = $stats->get($rec->id);

            $rec->impressions = $stat ? $stat->impressions : 0;
            $rec->clicks = $stat ? $stat->clicks : 0;
            $rec->conversions = $stat ? $stat->conversions : 0;
            $rec->conversion_rate = $stat && $stat->clicks > 0
                ? round(($stat->conversions / $stat->clicks) * 100, 2)
                : 0;

            return $rec;
        });

        return response()->json([
            'recommendations' => $recs,
            'products'        => $products->values(), // reset keys for clean JSON
        ]);
    }

    public function createRecommendation(Request $request): JsonResponse
    {
        $shopDomain = $request->input('shop');
        $shop = Shop::where('shopify_domain', $shopDomain)->first();

        if (!$shop) {
            return response()->json(['success' => false, 'message' => 'Shop not found'], 404);
        }

        $validated = $request->validate([
            'product_id'             => 'required|numeric',
            'recommended_product_id' => 'required|numeric|different:product_id',
        ]);

        $recommendation = Recommendation::create([
            'shop_id'                => $shop->id,
            'product_id'             => $validated['product_id'],
            'recommended_product_id' => $validated['recommended_product_id'],
            'algo'                   => $request->input('algo', 'manual'),
            'enabled'                => $request->boolean('enabled', true),
            'meta'                   => [],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Recommendation created.',
            'data'    => $recommendation,
        ], 201);
    }

    public function bulkCreateRecommendations(Request $request)
    {
        $validated = $request->validate([
            'shop' => 'required|string',
            'product_id' => 'required|numeric',
            'recommended_product_ids' => 'required|array|min:1',
            'recommended_product_ids.*' => 'numeric|different:product_id',
            'algo' => 'nullable|string',
        ]);

        $shop = Shop::where('shopify_domain', $validated['shop'])->first();
        if (!$shop) {
            return response()->json(['success' => false, 'message' => 'Shop not found'], 404);
        }

        $existingPairs = Recommendation::where('product_id', $validated['product_id'])
            ->whereIn('recommended_product_id', $validated['recommended_product_ids'])
            ->pluck('recommended_product_id')
            ->toArray();

        $newIds = array_diff($validated['recommended_product_ids'], $existingPairs);

        $recommendations = collect($newIds)->map(fn($recommendedId) => [
            'shop_id' => $shop->id,
            'product_id' => $validated['product_id'],
            'recommended_product_id' => $recommendedId,
            'algo' => $validated['algo'] ?? 'manual',
            'enabled' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Recommendation::insert($recommendations->toArray());

        return response()->json([
            'created' => $recommendations->count(),
            'skipped' => count($validated['recommended_product_ids']) - $recommendations->count(),
        ]);
    }

    public function update(Request $request, $id): JsonResponse
    {
        $request->validate([
            'product_id' => 'required|numeric',
            'recommended_product_id' => 'required|numeric|different:product_id',
        ]);

        $rec = Recommendation::findOrFail($id);
        $rec->update([
            'product_id' => $request->input('product_id'),
            'recommended_product_id' => $request->input('recommended_product_id'),
        ]);

        return response()->json(['success' => true, 'data' => $rec]);
    }

    public function delete($id, Request $request)
    {
        $rec = Recommendation::find($id);

        if (!$rec) {
            return response()->json(['success' => false, 'message' => 'Recommendation not found'], 404);
        }

        $rec->delete();

        return response()->json([
            'success' => true,
            'message' => 'Recommendation deleted.',
        ]);
    }

    public function toggleEnabled(Request $request, $id)
    {
        $recommendation = Recommendation::findOrFail($id);
        $recommendation->enabled = $request->has('enabled') && $request->input('enabled');
        $recommendation->save();

        return response()->json(['success' => true]);
    }
}
