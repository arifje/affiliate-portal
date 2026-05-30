<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProductView;
use App\Models\Site;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class ProductViewController extends Controller
{
    public function __invoke(Request $request, Site $site, string $productSlug): JsonResponse
    {
        $product = $site->products()
            ->where('slug', $productSlug)
            ->where('is_active', true)
            ->firstOrFail();

        $viewedAt = Carbon::now($site->timezone ?: config('app.timezone'));
        $viewedOn = $viewedAt->toDateString();
        $visitorHash = $this->visitorHash($request);

        $productView = ProductView::query()
            ->where('product_id', $product->id)
            ->where('visitor_hash', $visitorHash)
            ->whereDate('viewed_on', $viewedOn)
            ->first();

        $isUniqueView = $productView === null;

        if ($isUniqueView) {
            $productView = new ProductView([
                'product_id' => $product->id,
                'visitor_hash' => $visitorHash,
                'viewed_on' => $viewedOn,
            ]);
            $productView->site_id = $site->id;
            $productView->first_viewed_at = $viewedAt;
            $productView->view_count = 0;
        }

        $productView->fill([
            'site_id' => $site->id,
            'ip_hash' => $request->ip() ? $this->hash('ip:'.$request->ip()) : null,
            'user_agent_hash' => $request->userAgent() ? $this->hash('ua:'.$request->userAgent()) : null,
            'referer' => Str::limit((string) ($request->input('path') ?: $request->headers->get('referer')), 2000, ''),
            'last_viewed_at' => $viewedAt,
            'view_count' => ((int) $productView->view_count) + 1,
            'metadata' => [
                'source' => 'frontend_product_page',
            ],
        ]);

        $productView->save();

        return response()->json([
            'tracked' => true,
            'unique' => $isUniqueView,
        ], $isUniqueView ? 201 : 200);
    }

    private function visitorHash(Request $request): string
    {
        $visitorId = trim((string) $request->input('visitor_id'));

        if ($visitorId !== '') {
            return $this->hash('visitor:'.$visitorId);
        }

        return $this->hash('fallback:'.implode('|', [
            $request->ip(),
            $request->userAgent(),
        ]));
    }

    private function hash(string $value): string
    {
        return hash_hmac('sha256', $value, (string) config('app.key'));
    }
}
