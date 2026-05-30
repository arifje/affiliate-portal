<?php

use App\Http\Controllers\Api\ProductClickController;
use App\Http\Controllers\Api\ProductViewController;
use App\Http\Controllers\Api\SitePreviewController;
use App\Http\Controllers\Api\SitePreviewProductController;
use App\Http\Controllers\Api\SitePreviewProductIndexController;
use App\Http\Controllers\Api\SiteVisitController;
use App\Http\Middleware\EnsureWebsiteIsOnline;
use Illuminate\Support\Facades\Route;

Route::middleware(EnsureWebsiteIsOnline::class)->group(function (): void {
    Route::get('/sites/preview/{site:slug}', SitePreviewController::class)
        ->name('api.sites.preview');

    Route::get('/sites/preview/{site:slug}/products', SitePreviewProductIndexController::class)
        ->name('api.sites.preview.products.index');

    Route::get('/sites/preview/{site:slug}/products/{productSlug}', SitePreviewProductController::class)
        ->name('api.sites.preview.products.show');

    Route::post('/sites/preview/{site:slug}/visits', SiteVisitController::class)
        ->name('api.sites.preview.visits.store');

    Route::post('/sites/preview/{site:slug}/products/{productSlug}/clicks', ProductClickController::class)
        ->name('api.sites.preview.products.clicks.store');

    Route::post('/sites/preview/{site:slug}/products/{productSlug}/views', ProductViewController::class)
        ->name('api.sites.preview.products.views.store');
});
