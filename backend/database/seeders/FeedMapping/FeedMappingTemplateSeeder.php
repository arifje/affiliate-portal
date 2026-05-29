<?php

namespace Database\Seeders\FeedMapping;

use App\Models\CanonicalField;
use App\Models\FeedFieldMapping;
use App\Models\FeedMappingProfile;
use Illuminate\Database\Seeder;

class FeedMappingTemplateSeeder extends Seeder
{
    /**
     * Seed reusable provider mapping profiles.
     */
    public function run(): void
    {
        foreach (config('feed-mapping.provider_templates', []) as $template) {
            $profile = FeedMappingProfile::query()->updateOrCreate(
                [
                    'provider' => $template['provider'],
                    'slug' => $template['slug'],
                ],
                [
                    'site_id' => null,
                    'partner_id' => null,
                    'name' => $template['name'],
                    'source_format' => $template['source_format'] ?? 'csv',
                    'source_encoding' => $template['source_encoding'] ?? 'utf-8',
                    'delimiter' => $template['delimiter'] ?? null,
                    'enclosure' => $template['enclosure'] ?? null,
                    'decimal_separator' => $template['decimal_separator'] ?? '.',
                    'thousands_separator' => $template['thousands_separator'] ?? null,
                    'currency' => $template['currency'] ?? 'EUR',
                    'locale' => $template['locale'] ?? 'nl_NL',
                    'timezone' => $template['timezone'] ?? 'Europe/Amsterdam',
                    'row_selector' => $template['row_selector'] ?? null,
                    'first_row_is_header' => $template['first_row_is_header'] ?? true,
                    'is_template' => true,
                    'is_active' => $template['is_active'] ?? true,
                    'settings' => $template['settings'] ?? null,
                ]
            );

            $sortOrder = 0;

            foreach ($template['mappings'] ?? [] as $canonicalKey => $mapping) {
                $canonicalField = CanonicalField::query()->where('key', $canonicalKey)->first();

                if (! $canonicalField) {
                    continue;
                }

                FeedFieldMapping::query()->updateOrCreate(
                    [
                        'feed_mapping_profile_id' => $profile->id,
                        'canonical_field_id' => $canonicalField->id,
                    ],
                    [
                        'source_field' => $mapping['source_field'] ?? null,
                        'source_path' => $mapping['source_path'] ?? null,
                        'fallback_fields' => $mapping['fallback_fields'] ?? null,
                        'default_value' => $mapping['default_value'] ?? null,
                        'transform_type' => $mapping['transform_type'] ?? 'copy',
                        'transform_config' => $mapping['transform_config'] ?? null,
                        'is_required' => $mapping['is_required'] ?? $canonicalField->is_required,
                        'sort_order' => $mapping['sort_order'] ?? ($sortOrder += 10),
                    ]
                );
            }
        }
    }
}
