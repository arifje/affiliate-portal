<?php

namespace Database\Seeders\FeedMapping;

use App\Models\CanonicalField;
use Illuminate\Database\Seeder;

class CanonicalFieldSeeder extends Seeder
{
    /**
     * Seed the canonical product field registry.
     */
    public function run(): void
    {
        foreach (config('feed-mapping.canonical_fields', []) as $field) {
            CanonicalField::query()->updateOrCreate(
                ['key' => $field['key']],
                [
                    'field_group' => $field['field_group'],
                    'label' => $field['label'],
                    'description' => $field['description'] ?? null,
                    'data_type' => $field['data_type'],
                    'target_column' => $field['target_column'] ?? null,
                    'metadata_path' => $field['metadata_path'] ?? null,
                    'is_required' => $field['is_required'] ?? false,
                    'is_searchable' => $field['is_searchable'] ?? false,
                    'is_filterable' => $field['is_filterable'] ?? false,
                    'is_variant' => $field['is_variant'] ?? false,
                    'validation_rules' => $field['validation_rules'] ?? null,
                    'options' => $field['options'] ?? null,
                    'sort_order' => $field['sort_order'] ?? 0,
                    'is_active' => $field['is_active'] ?? true,
                ]
            );
        }
    }
}
