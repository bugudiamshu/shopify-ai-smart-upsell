<?php

use App\Http\Controllers\Api\AuthCheckController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\RecommendationsController;
use App\Http\Controllers\Api\RecommendationStatsController;
use App\Http\Controllers\Api\UpsellController;
use Illuminate\Support\Facades\Route;

Route::get('/check-shop', [AuthCheckController::class, 'check']);

Route::post('/products/sync', [ProductController::class, 'syncProducts'])->name('products.sync');
Route::get('/products', [ProductController::class, 'getProducts']);

Route::get('/recommendations', [RecommendationsController::class, 'index']);
Route::post('/recommendations/create', [RecommendationsController::class, 'createRecommendation'])->name('recommendations.create');
Route::post('/recommendations/bulk-create', [RecommendationsController::class, 'bulkCreateRecommendations']);
Route::patch('/recommendations/{id}', [RecommendationsController::class, 'update']);
Route::delete('/recommendations/{id}', [RecommendationsController::class, 'delete'])->name('recommendations.delete');
Route::patch('/recommendations/{id}/toggle', [RecommendationsController::class, 'toggleEnabled'])->name('recommendations.toggle');

Route::get('/upsells', [UpsellController::class, 'fetch']);
Route::post('/upsell-clicks', [UpsellController::class, 'recordClick'])->name('api.upsell.clicks');
Route::post('/upsell-impressions', [UpsellController::class, 'recordImpression'])->name('api.upsell.impressions');

Route::get('/recommendation-stats', [RecommendationStatsController::class, 'index']);
