<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RecommendationStatsController extends Controller
{
    /**
     * Return aggregated stats for impressions, clicks, and conversions for the given shop.
     */
    public function index(Request $request)
    {
        $shop = $request->query('shop');

        if (!$shop) {
            return response()->json(['error' => 'Shop parameter is required'], 400);
        }

        // For example, get shop ID from shops table
        $shopRecord = DB::table('shops')->where('shopify_domain', $shop)->first();
        if (!$shopRecord) {
            return response()->json(['error' => 'Shop not found'], 404);
        }
        $shopId = $shopRecord->id;

        // Aggregate impressions count
        $totalImpressions = DB::table('upsell_impressions')
            ->where('shop_id', $shopId)
            ->count();

        // Aggregate clicks count
        $totalClicks = DB::table('upsell_clicks')
            ->where('shop_id', $shopId)
            ->count();

        // Aggregate conversions count
        $totalConversions = DB::table('recommendation_conversions')
            ->where('shop_id', $shopId)
            ->count();

        // Optional: aggregate stats per recommendation
        $perRecommendationStats = DB::table('recommendations as r')
            ->leftJoin('upsell_impressions as i', 'r.product_id', '=', 'i.product_id')
            ->leftJoin('upsell_clicks as c', 'r.id', '=', 'c.recommendation_id')
            ->leftJoin('recommendation_conversions as conv', 'r.id', '=', 'conv.recommendation_id')
            ->where('r.shop_id', $shopId)
            ->select(
                'r.id as recommendation_id',
                DB::raw('COUNT(DISTINCT i.id) as impressions'),
                DB::raw('COUNT(DISTINCT c.id) as clicks'),
                DB::raw('COUNT(DISTINCT conv.id) as conversions')
            )
            ->groupBy('r.id')
            ->get();

        return response()->json([
            'total_impressions' => $totalImpressions,
            'total_clicks' => $totalClicks,
            'total_conversions' => $totalConversions,
            'recommendations' => $perRecommendationStats,
        ]);
    }
}
