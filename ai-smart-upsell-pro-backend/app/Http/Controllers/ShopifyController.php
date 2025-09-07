<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\Shop;
use Illuminate\Support\Facades\Log;

class ShopifyController extends Controller
{
    public function install(Request $request)
    {
        $shop        = $request->input('shop');
        $apiKey      = config('services.shopify.key');
        $scopes      = 'read_products,write_products,read_script_tags,write_script_tags,read_orders,write_orders,read_customers';
        $redirectUri = urlencode(secure_url('shopify/callback'));

        $installUrl = "https://{$shop}/admin/oauth/authorize?client_id={$apiKey}&scope={$scopes}&redirect_uri={$redirectUri}&state=nonce&grant_mode=offline";

        return redirect($installUrl);
    }

    public function callback(Request $request)
    {
        $shop = $request->input('shop');
        $code = $request->input('code');

        $response = Http::asForm()->post("https://{$shop}/admin/oauth/access_token", [
            'client_id'     => config('services.shopify.key'),
            'client_secret' => config('services.shopify.secret'),
            'code'          => $code,
        ]);

        $data        = $response->json();
        $accessToken = $data['access_token'] ?? null;

        if ($accessToken) {

            $shopInfoResponse = Http::withHeaders([
                'X-Shopify-Access-Token' => $accessToken,
            ])->get("https://{$shop}/admin/api/2024-04/shop.json");

            $shopInfo = $shopInfoResponse->json('shop');

            $shopRecord = Shop::updateOrCreate(
                ['shopify_domain' => $shop],
                [
                    'shopify_token' => $accessToken,
                    'currency'      => $shopInfo['currency'] ?? 'USD',
                    'money_format'  => $shopInfo['money_with_currency_format'],
                ]
            );

            $this->registerWebhooks($shopRecord);
//            $this->registerJs($shopRecord);

            $host = $request->input('host');

            $frontendUrl = config('app.frontend_url', 'https://shopifyfrontend.bcstdr.site');
            return redirect()->to("{$frontendUrl}/?shop={$shop}&host={$host}");
//            return redirect()->route('dashboard', ['shop' => $shop])->with('shop', $shop);
        }

        abort(400, 'Unable to authenticate.');
    }

    public function registerWebhooks($shop)
    {
        $token          = $shop->shopify_token;
        $domain         = $shop->shopify_domain;
        $webhookAddress = secure_url('/webhooks/orders-create');

        $existing = Http::withHeaders([
            'X-Shopify-Access-Token' => $token,
            'Content-Type'           => 'application/json',
        ])->get("https://{$domain}/admin/api/2024-04/webhooks.json");

        if ($existing->failed()) {
            Log::error('Failed to fetch existing webhooks', [$existing->json()]);

            return;
        }

        $alreadyExists = collect($existing->json('webhooks', []))
            ->contains(fn($webhook) => $webhook['topic'] === 'orders/create' && $webhook['address'] === $webhookAddress);

        if ($alreadyExists) {
            Log::info("Webhook for 'orders/create' already exists. Skipping registration.");

            return;
        }

        $webhookData = [
            'webhook' => [
                'topic'   => 'orders/create',
                'address' => $webhookAddress,
                'format'  => 'json',
            ],
        ];

        $response = Http::withHeaders([
            'X-Shopify-Access-Token' => $token,
            'Content-Type'           => 'application/json',
        ])->post("https://{$domain}/admin/api/2024-04/webhooks.json", $webhookData);

        if ($response->failed()) {
            Log::error('Failed to register "orders/create" webhook', [$response->json()]);
        } else {
            Log::info('Successfully registered "orders/create" webhook');
        }
    }

    public function registerJs($shop)
    {
        $token     = $shop->shopify_token;
        $domain    = $shop->shopify_domain;
        $scriptSrc = config('app.url') . '/js/upsell.js';

        $existing = Http::withHeaders([
            'X-Shopify-Access-Token' => $token,
        ])->get("https://{$domain}/admin/api/2024-04/script_tags.json");

        if ($existing->failed()) {
            Log::error('Failed to fetch existing ScriptTags', [$existing->json()]);

            return;
        }

        $alreadyExists = collect($existing->json('script_tags', []))
            ->contains(fn($script) => $script['src'] === $scriptSrc);

        if ($alreadyExists) {
            Log::info('ScriptTag already exists. Skipping registration.');

            return;
        }

        $response = Http::withHeaders([
            'X-Shopify-Access-Token' => $token,
            'Content-Type'           => 'application/json',
        ])->post("https://{$domain}/admin/api/2024-04/script_tags.json", [
            'script_tag' => [
                'event' => 'onload',
                'src'   => $scriptSrc,
            ],
        ]);

        if ($response->failed()) {
            Log::error('Failed to register ScriptTag', [$response->json()]);
        } else {
            Log::info('Successfully registered ScriptTag for upsell.js');
        }
    }
}
