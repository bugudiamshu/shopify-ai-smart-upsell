<?php

namespace App\Console\Commands;

use App\Models\Recommendation;
use App\Models\Shop;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use OpenAI\Laravel\Facades\OpenAI;

class GenerateAIRecommendations extends Command
{
    protected $signature = 'generate:ai-recommendations';
    protected $description = 'Generate AI-based product recommendations for all shops';

    public function handle()
    {
        $shops = Shop::with('products')->get();

        foreach ($shops as $shop) {
            $this->info("Processing shop: {$shop->shopify_domain}");
            $products = $shop->products;
            $embeddings = [];

            // 1. Generate embeddings for each product
            foreach ($products as $product) {
                $inputText = trim($product->title . ' ' . strip_tags($product->body_html));
                $embedding = cache()->remember(
                    "embedding:{$shop->id}:{$product->shopify_product_id}",
                    86400,
                    function () use ($inputText) {
                        $res = OpenAI::embeddings()->create([
                            'model' => 'text-embedding-3-small',
                            'input' => $inputText,
                        ]);
                        return $res['data'][0]['embedding'] ?? null;
                    }
                );
                if ($embedding) {
                    $embeddings[$product->shopify_product_id] = $embedding;
                }
            }

            Log::info(json_encode($embeddings, JSON_PRETTY_PRINT));

            // 2. For each product, find the 3 most similar other products
            foreach ($products as $product) {
                $baseId = $product->shopify_product_id;
                $baseEmbedding = $embeddings[$baseId] ?? null;
                if (!$baseEmbedding) continue;

                $scores = [];
                foreach ($embeddings as $otherId => $otherEmbedding) {
                    if ($baseId == $otherId) continue;
                    $sim = $this->cosineSimilarity($baseEmbedding, $otherEmbedding);
                    $scores[$otherId] = $sim;
                }

                arsort($scores);
                $top = array_slice($scores, 0, 3, true);

                // Remove previous AI recs for this product
                Recommendation::where('shop_id', $shop->id)
                    ->where('product_id', $baseId)
                    ->where('algo', 'ai')
                    ->delete();

                foreach ($top as $recId => $similarity) {
                    Recommendation::updateOrCreate(
                        [
                            'shop_id'                => $shop->id,
                            'product_id'             => $baseId,
                            'recommended_product_id' => $recId,
                        ],
                        [
                            'algo' => 'ai',
                            'meta' => ['score' => $similarity]
                        ]
                    );
                }
                $this->info("AI recommendations set for product: {$product->title}");
            }
        }
        $this->info("AI recommendation generation complete.");
    }

    // Helper method for cosine similarity between two vectors
    public function cosineSimilarity($a, $b)
    {
        $dot = 0.0;
        $magA = 0.0;
        $magB = 0.0;
        for ($i = 0; $i < count($a); $i++) {
            $dot += $a[$i] * $b[$i];
            $magA += $a[$i] ** 2;
            $magB += $b[$i] ** 2;
        }
        if ($magA == 0.0 || $magB == 0.0) return 0.0;
        return $dot / (sqrt($magA) * sqrt($magB));
    }
}
