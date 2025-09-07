<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Shop;

class AuthCheckController extends Controller
{
    public function check(Request $request)
    {
        $shop = $request->query('shop'); // query is more explicit for GET params

        if (!$shop) {
            return response()->json(['error' => 'Missing shop parameter'], 400);
        }

        // Normalize shop domain
        $shop = strtolower(trim($shop));
        $shop = preg_replace('#^https?://#', '', $shop); // remove protocol if present

        $shopRecord = Shop::whereRaw('LOWER(shopify_domain) = ?', [$shop])
            ->whereNotNull('shopify_token')
            ->first();

        if (!$shopRecord) {
            return response()->json(['authenticated' => false], 401); // use 401 for "unauthorized"
        }

        return response()->json(['authenticated' => true], 200);
    }
}
