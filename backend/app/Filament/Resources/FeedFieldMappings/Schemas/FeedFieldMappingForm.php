<?php

namespace App\Filament\Resources\FeedFieldMappings\Schemas;

use App\Models\FeedMappingProfile;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class FeedFieldMappingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('admin.sections.mapping'))
                    ->schema([
                        Select::make('feed_mapping_profile_id')
                            ->label(__('admin.fields.mapping_profile'))
                            ->relationship('profile', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Select::make('canonical_field_id')
                            ->label(__('admin.fields.canonical_field'))
                            ->relationship('canonicalField', 'key')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Select::make('mapping_action')
                            ->label(__('admin.fields.mapping_action'))
                            ->options(__('admin.options.mapping_actions'))
                            ->required()
                            ->default('map')
                            ->helperText(__('admin.helpers.mapping_action')),
                        TextInput::make('source_field')
                            ->label(__('admin.fields.source_field'))
                            ->datalist(fn (Get $get): array => self::sampleFieldOptions($get('feed_mapping_profile_id')))
                            ->maxLength(255),
                        Textarea::make('source_path')
                            ->label(__('admin.fields.source_path'))
                            ->columnSpanFull(),
                        Textarea::make('source_sample')
                            ->label(__('admin.fields.source_sample'))
                            ->rows(2)
                            ->columnSpanFull(),
                        TagsInput::make('fallback_fields')
                            ->label(__('admin.fields.fallback_fields'))
                            ->columnSpanFull(),
                        Textarea::make('default_value')
                            ->label(__('admin.fields.default_value'))
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                Section::make(__('admin.sections.transform'))
                    ->schema([
                        Select::make('transform_type')
                            ->label(__('admin.fields.transform_type'))
                            ->options(__('admin.options.transform_types'))
                            ->required()
                            ->default('copy'),
                        TextInput::make('sort_order')
                            ->label(__('admin.fields.sort_order'))
                            ->required()
                            ->numeric()
                            ->default(0),
                        Toggle::make('is_required')
                            ->label(__('admin.fields.is_required'))
                            ->required(),
                        KeyValue::make('transform_config')
                            ->label(__('admin.fields.transform_config'))
                            ->columnSpanFull(),
                        Textarea::make('notes')
                            ->label(__('admin.fields.notes'))
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(3),
            ]);
    }

    /**
     * @return array<int, string>
     */
    private static function sampleFieldOptions(mixed $profileId): array
    {
        if (! $profileId) {
            return [];
        }

        $profile = FeedMappingProfile::query()->find($profileId);

        return collect($profile?->sample_fields ?? [])
            ->pluck('path')
            ->filter()
            ->values()
            ->all();
    }
}
