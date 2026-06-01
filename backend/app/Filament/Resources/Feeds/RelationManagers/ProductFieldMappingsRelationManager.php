<?php

namespace App\Filament\Resources\Feeds\RelationManagers;

use App\Models\CanonicalField;
use App\Models\Feed;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class ProductFieldMappingsRelationManager extends RelationManager
{
    protected static string $relationship = 'productFieldMappings';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('admin.resources.product_field_mappings.plural_label');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('admin.sections.product_field_mapping'))
                    ->schema([
                        Select::make('canonical_field_id')
                            ->label(__('admin.fields.product_field'))
                            ->relationship('canonicalField', 'label')
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
                            ->datalist(fn (): array => $this->sampleFieldPaths())
                            ->maxLength(255),
                        Textarea::make('source_path')
                            ->label(__('admin.fields.source_path'))
                            ->helperText(__('admin.helpers.source_path'))
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

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('source_field')
            ->columns([
                TextColumn::make('canonicalField.label')
                    ->label(__('admin.fields.product_field'))
                    ->description(fn ($record): ?string => $record->canonicalField?->key)
                    ->searchable(),
                TextColumn::make('canonicalField.field_group')
                    ->label(__('admin.fields.field_group'))
                    ->badge()
                    ->toggleable(),
                TextColumn::make('mapping_action')
                    ->label(__('admin.fields.mapping_action'))
                    ->badge()
                    ->searchable(),
                TextColumn::make('source_field')
                    ->label(__('admin.fields.source_field'))
                    ->searchable()
                    ->placeholder('-'),
                TextColumn::make('source_sample')
                    ->label(__('admin.fields.source_sample'))
                    ->limit(40)
                    ->toggleable(),
                TextColumn::make('transform_type')
                    ->label(__('admin.fields.transform_type'))
                    ->badge()
                    ->searchable(),
                IconColumn::make('is_required')
                    ->label(__('admin.fields.is_required'))
                    ->boolean(),
                TextColumn::make('sort_order')
                    ->label(__('admin.fields.sort_order'))
                    ->numeric()
                    ->sortable(),
                TextColumn::make('updated_at')
                    ->label(__('admin.fields.updated_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('mapping_action')
                    ->label(__('admin.fields.mapping_action'))
                    ->options(__('admin.options.mapping_actions')),
            ])
            ->headerActions([
                Action::make('createDraftMappings')
                    ->label(__('admin.actions.create_draft_mappings'))
                    ->icon(Heroicon::OutlinedSparkles)
                    ->requiresConfirmation()
                    ->modalDescription(__('admin.messages.create_draft_mappings'))
                    ->action(fn () => $this->createDraftMappings()),
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('sort_order');
    }

    /**
     * @return array<int, string>
     */
    private function sampleFieldPaths(): array
    {
        /** @var Feed $feed */
        $feed = $this->getOwnerRecord();

        return collect($feed->sample_fields ?? [])
            ->pluck('path')
            ->filter()
            ->values()
            ->all();
    }

    private function createDraftMappings(): void
    {
        /** @var Feed $feed */
        $feed = $this->getOwnerRecord();
        $sampleFields = collect($feed->sample_fields ?? []);
        $existingCanonicalIds = $feed->productFieldMappings()->pluck('canonical_field_id')->all();
        $sortOrder = (int) ($feed->productFieldMappings()->max('sort_order') ?? 0);
        $created = 0;

        CanonicalField::query()
            ->active()
            ->whereNotIn('id', $existingCanonicalIds)
            ->orderBy('field_group')
            ->orderBy('sort_order')
            ->orderBy('label')
            ->get()
            ->each(function (CanonicalField $field) use ($feed, $sampleFields, &$sortOrder, &$created): void {
                $templateMapping = $this->templateMappingFor($field, $feed);
                $match = $this->guessSampleField($field, $sampleFields, $templateMapping);
                $sourceField = $match['path'] ?? $templateMapping['source_field'] ?? null;

                $feed->productFieldMappings()->create([
                    'canonical_field_id' => $field->id,
                    'mapping_action' => $sourceField ? 'map' : 'skip',
                    'source_field' => $sourceField,
                    'source_path' => $sourceField,
                    'source_sample' => $match['sample'] ?? null,
                    'fallback_fields' => $templateMapping['fallback_fields'] ?? [],
                    'default_value' => $templateMapping['default_value'] ?? null,
                    'transform_type' => $templateMapping['transform_type'] ?? $this->defaultTransformForField($field),
                    'transform_config' => $templateMapping['transform_config'] ?? null,
                    'is_required' => $field->is_required,
                    'sort_order' => ++$sortOrder,
                ]);

                $created++;
            });

        Notification::make()
            ->success()
            ->title(__('admin.messages.draft_mappings_created', ['count' => $created]))
            ->send();
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $sampleFields
     * @param  array<string, mixed>|null  $templateMapping
     * @return array<string, mixed>|null
     */
    private function guessSampleField(CanonicalField $field, Collection $sampleFields, ?array $templateMapping): ?array
    {
        $needles = collect([
            $field->key,
            $field->target_column,
            $field->label,
            $templateMapping['source_field'] ?? null,
            ...($templateMapping['fallback_fields'] ?? []),
        ])
            ->filter()
            ->map(fn (string $value): string => $this->normalizeFieldName($value))
            ->unique()
            ->values();

        return $sampleFields
            ->first(function (array $sampleField) use ($needles): bool {
                $path = $this->normalizeFieldName((string) ($sampleField['path'] ?? ''));
                $label = $this->normalizeFieldName((string) ($sampleField['label'] ?? ''));

                return $needles->contains($path) || $needles->contains($label);
            });
    }

    /**
     * @return array<string, mixed>|null
     */
    private function templateMappingFor(CanonicalField $field, Feed $feed): ?array
    {
        $templates = collect(config('feed-mapping.provider_templates', []))
            ->where('provider', $feed->provider);

        foreach ($templates as $template) {
            $mapping = $template['mappings'][$field->key] ?? null;

            if ($mapping) {
                return $mapping;
            }
        }

        return null;
    }

    private function normalizeFieldName(string $value): string
    {
        return str($value)
            ->lower()
            ->replace(['.', '-', ' '], '_')
            ->replaceMatches('/[^a-z0-9_]/', '')
            ->toString();
    }

    private function defaultTransformForField(CanonicalField $field): string
    {
        return match ($field->data_type) {
            'boolean' => 'boolean',
            'decimal' => 'decimal',
            'integer' => 'integer',
            'array' => 'array',
            'url' => 'url',
            default => 'copy',
        };
    }
}
