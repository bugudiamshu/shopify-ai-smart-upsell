<?php

namespace App\Http\Controllers;

use App\Models\Shop;
use Illuminate\Http\Request;
use App\Models\User; // or your custom shop model

class GDPRController extends Controller
{
    // Handles customer data request webhook
    public function customerRequest(Request $request)
    {
        // You would normally return all customer data if you store any.
        // For a basic upsell app that doesn't store Shopify customer data, return an empty array.
        return response()->json([]);
    }

    // Handles customer data erasure webhook
    public function customerErasure(Request $request)
    {
        // If you do store customer data, erase it based on Shopify's request payload.
        // For most Shopify embedded admin apps, nothing needs to be done here.
        return response('Customer data erased', 200);
    }

    // Handles shop data erasure webhook (when a store uninstalls your app)
    public function shopErasure(Request $request)
    {
        $shopDomain = $request->input('shop_domain');

        // Delete the merchant/shop record and related data (products, recommendations, etc)
        Shop::where('shopify_domain', $shopDomain)->delete();

        return response('Shop data erased', 200);
    }
}
