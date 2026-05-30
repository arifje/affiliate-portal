<?php

use App\Models\Site;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('sites')
            ->whereNotNull('settings')
            ->orderBy('id')
            ->each(function (object $site): void {
                $settings = json_decode((string) $site->settings, true);

                if (! is_array($settings)) {
                    return;
                }

                $heroImage = $settings['hero_image'] ?? null;

                if (! is_string($heroImage) || ! str_starts_with($heroImage, 'site-heroes/')) {
                    return;
                }

                $targetPath = Site::storageDirectoryFor($site->slug, $site->id, 'hero').'/'.basename($heroImage);
                $disk = Storage::disk('public');

                if ($disk->exists($heroImage) && ! $disk->exists($targetPath)) {
                    $disk->move($heroImage, $targetPath);
                }

                $settings['hero_image'] = $targetPath;

                DB::table('sites')
                    ->where('id', $site->id)
                    ->update([
                        'settings' => json_encode($settings),
                        'updated_at' => now(),
                    ]);
            });
    }

    public function down(): void
    {
        //
    }
};
