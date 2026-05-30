<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Click;
use App\Models\Site;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class ProductClickController extends Controller
{
    public function __invoke(Request $request, Site $site, string $productSlug): JsonResponse
    {
        $product = $site->products()
            ->where('slug', $productSlug)
            ->where('is_active', true)
            ->firstOrFail();

        Click::query()->create([
            'site_id' => $site->id,
            'product_id' => $product->id,
            'partner_id' => $product->partner_id,
            'feed_id' => $product->feed_id,
            'target_url' => $product->affiliate_url,
            'referer' => Str::limit((string) ($request->input('path') ?: $request->headers->get('referer')), 2000, ''),
            'ip_address' => $request->ip(),
            'user_agent' => Str::limit((string) $request->userAgent(), 2000, ''),
            'session_id' => $request->hasSession() ? $request->session()->getId() : null,
            'visitor_id' => Str::limit(trim((string) $request->input('visitor_id')), 255, ''),
            'metadata' => [
                'source' => 'frontend_product_cta',
            ],
            'clicked_at' => Carbon::now($site->timezone ?: config('app.timezone')),
        ]);

        return response()->json([
            'tracked' => true,
        ], 201);
    }
}
