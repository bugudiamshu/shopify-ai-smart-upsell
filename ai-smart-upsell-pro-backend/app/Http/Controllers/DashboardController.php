<?php

namespace App\Http\Controllers;

use App\Models\Shop;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $shopDomain = $request->query('shop') ?? session('shop');
        if (!$shopDomain) {
            return redirect()->route('shopify.install')->with('error', 'Please connect your Shopify store.');
        }

        $shop = Shop::where('shopify_domain', $shopDomain)->first();

        if (!$shop) {
            return redirect()->route('shopify.install')->with('error', 'Shop not found. Please reinstall the app.');
        }

        $products = $shop->products()->latest()->paginate(10)->appends(['shop' => $shopDomain]);;

        return view('dashboard', compact('shop', 'products'));
    }
}
