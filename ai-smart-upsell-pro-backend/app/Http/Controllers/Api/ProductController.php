<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Shop;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ProductController extends Controller
{
    public function syncProducts(Request $request)
    {
        $shopDomain = $request->input('shop') ?? $request->query('shop') ?? session('shop');
        $shop       = Shop::where('shopify_domain', $shopDomain)->firstOrFail();
        $token      = $shop->shopify_token;

        try {
            $response = Http::withHeaders([
                'X-Shopify-Access-Token' => $token,
                'Accept'                 => 'application/json',
            ])->get("https://{$shop->shopify_domain}/admin/api/2024-04/products.json?limit=250");

            if ($response->ok()) {
                $products = $response->json('products', []);
                Log::info(json_encode(array_column($products, 'handle')));
                foreach ($products as $prod) {
                    Product::updateOrCreate(
                        [
                            'shop_id'            => $shop->id,
                            'shopify_product_id' => $prod['id'],
                        ],
                        [
                            'title'        => $prod['title'],
                            'body_html'    => $prod['body_html'] ?? null,
                            'vendor'       => $prod['vendor'] ?? null,
                            'product_type' => $prod['product_type'] ?? null,
                            'images'       => $prod['images'] ?? [],
                            'price'        => $prod['variants'][0]['price'] ?? null,
                            'variant_id'   => $prod['variants'][0]['id'] ?? null,
                            'handle'       => $prod['handle'],
                        ]
                    );
                }

                return response()->json(['success' => true, 'products' => Product::all()->toArray()]);
            } else {
                Log::error('Shopify API product sync failed', ['response' => $response->body()]);

                return response()->json(['success' => false]);
            }
        } catch (\Exception $e) {
            Log::error('Exception during product sync', ['error' => $e->getMessage()]);

            return response()->json(['success' => false]);
        }
    }

    public function getProducts(Request $request): JsonResponse
    {
        $shopDomain = $request->input('shop') ?? $request->query('shop');
        $search     = $request->input('search');

        if (!$shopDomain) {
            return response()->json(['error' => 'Missing shop domain'], 400);
        }

        $shop = Shop::where('shopify_domain', $shopDomain)->first();

        if (!$shop) {
            return response()->json(['error' => 'Shop not found'], 404);
        }

        // 🔍 Build query with basic filters
        $query = Product::where('shop_id', $shop->id);

        // Optional search by title
        if ($search) {
            $query->where('title', 'like', "%{$search}%");
        }

        // 🧾 Select only necessary fields + images for thumbnail preview
        $products = $query
            ->select('id', 'shopify_product_id', 'title', 'price', 'images')
            ->orderBy('title')
            ->limit(50)
            ->get();

        return response()->json($products);
    }
}
