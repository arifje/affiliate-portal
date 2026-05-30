<?php

use App\Http\Controllers\Api\ProductViewController;
use App\Http\Controllers\Api\SitePreviewController;
use App\Http\Controllers\Api\SitePreviewProductController;
use Illuminate\Support\Facades\Route;

Route::get('/sites/preview/{site:slug}', SitePreviewController::class)
    ->name('api.sites.preview');

Route::get('/sites/preview/{site:slug}/products/{productSlug}', SitePreviewProductController::class)
    ->name('api.sites.preview.products.show');

Route::post('/sites/preview/{site:slug}/products/{productSlug}/views', ProductViewController::class)
    ->name('api.sites.preview.products.views.store');
