<?php

namespace App\Http\Controllers;

use App\Models\Recommendation;
use App\Models\Shop;
use Illuminate\Http\Request;

class AnalyticsController extends Controller
{
    public function index(Request $request)
    {
        $shopDomain = $request->query('shop');
        $shop = Shop::where('shopify_domain', $shopDomain)->first();

        if (!$shop) {
            abort(404, 'Shop not found');
        }

        // Get each recommendation with number of conversions
        $recommendations = Recommendation::where('shop_id', $shop->id)
            ->withCount(['conversions'])
            ->paginate(20);

        return view('analytics.index', [
            'shop' => $shop,
            'recommendations' => $recommendations,
        ]);
    }
}
