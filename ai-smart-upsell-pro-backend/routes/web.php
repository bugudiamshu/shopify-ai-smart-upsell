<?php

use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GDPRController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\RecommendationsController;
use App\Http\Controllers\ShopifyController;
use App\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;

// Start install (redirect to Shopify)
Route::get('/shopify/install', [ShopifyController::class, 'install'])->name('shopify.install');

// Handle callback from Shopify (exchange code for token)
Route::get('/shopify/callback', [ShopifyController::class, 'callback'])->name('shopify.callback');

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::post('/recommendations/create', [RecommendationsController::class, 'createRecommendation'])->name('recommendations.create');
Route::delete('/recommendations/{id}', [RecommendationsController::class, 'delete'])->name('recommendations.delete');


Route::post('/webhooks/gdpr/customer-request', [GDPRController::class, 'customerRequest']);
Route::post('/webhooks/gdpr/customer-erasure', [GDPRController::class, 'customerErasure']);
Route::post('/webhooks/gdpr/shop-erasure', [GDPRController::class, 'shopErasure']);

Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');


Route::get('/recommendations', [RecommendationsController::class, 'showRecommendations'])->name('recommendations.index');
Route::post('/recommendations', [RecommendationsController::class, 'createRecommendation'])->name('recommendations.create');
Route::delete('/recommendations/{id}', [RecommendationsController::class, 'delete'])->name('recommendations.delete');


Route::post('/webhooks/orders-create', [WebhookController::class, 'orderCreate']);
Route::get('/analytics', [AnalyticsController::class, 'index'])->name('analytics.index');
