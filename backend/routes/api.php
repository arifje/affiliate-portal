<?php

use App\Http\Controllers\Api\SitePreviewController;
use Illuminate\Support\Facades\Route;

Route::get('/sites/preview/{site:slug}', SitePreviewController::class)
    ->name('api.sites.preview');
