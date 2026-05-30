<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Site;
use App\Models\SiteVisit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class SiteVisitController extends Controller
{
    public function __invoke(Request $request, Site $site): JsonResponse
    {
        $siteDate = Carbon::now($site->timezone ?: config('app.timezone'));
        $seenAt = now();
        $visitedOn = $siteDate->toDateString();
        $visitorHash = $this->visitorHash($request);
        $path = Str::limit((string) $request->input('path'), 2000, '');

        $siteVisit = SiteVisit::query()
            ->where('site_id', $site->id)
            ->where('visitor_hash', $visitorHash)
            ->whereDate('visited_on', $visitedOn)
            ->first();

        $isUniqueVisitor = $siteVisit === null;

        if ($isUniqueVisitor) {
            $siteVisit = new SiteVisit([
                'site_id' => $site->id,
                'visitor_hash' => $visitorHash,
                'visited_on' => $visitedOn,
                'first_visited_at' => $seenAt,
                'landing_path' => $path,
                'visit_count' => 0,
            ]);
        }

        $siteVisit->fill([
            'site_id' => $site->id,
            'ip_hash' => $request->ip() ? $this->hash('ip:'.$request->ip()) : null,
            'user_agent_hash' => $request->userAgent() ? $this->hash('ua:'.$request->userAgent()) : null,
            'last_path' => $path,
            'referer' => Str::limit((string) ($request->input('referer') ?: $request->headers->get('referer')), 2000, ''),
            'last_seen_at' => $seenAt,
            'visit_count' => ((int) $siteVisit->visit_count) + 1,
            'metadata' => [
                'source' => 'frontend_page_view',
            ],
        ]);

        $siteVisit->save();

        return response()->json([
            'tracked' => true,
            'unique' => $isUniqueVisitor,
        ], $isUniqueVisitor ? 201 : 200);
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
