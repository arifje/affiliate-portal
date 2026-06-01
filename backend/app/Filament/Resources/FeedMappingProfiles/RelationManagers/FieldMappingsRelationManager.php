<?php

namespace App\Filament\Resources\FeedMappingProfiles\RelationManagers;

use App\Models\CanonicalField;
use App\Models\FeedMappingProfile;
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
use Filament\Tables\Table;

class FieldMappingsRelationManager extends RelationManager
{
    protected static string $relationship = 'fieldMappings';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('admin.sections.mapping'))
                    ->schema([
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
                            ->datalist(fn (): array => $this->sampleFieldPaths())
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

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('source_field')
            ->columns([
                TextColumn::make('canonicalField.key')
                    ->label(__('admin.fields.canonical_field'))
                    ->searchable(),
                TextColumn::make('canonicalField.label')
                    ->label(__('admin.fields.label'))
                    ->toggleable(),
                TextColumn::make('source_field')
                    ->label(__('admin.fields.source_field'))
                    ->searchable(),
                TextColumn::make('source_sample')
                    ->label(__('admin.fields.source_sample'))
                    ->limit(40)
                    ->toggleable(),
                TextColumn::make('mapping_action')
                    ->label(__('admin.fields.mapping_action'))
                    ->badge()
                    ->searchable(),
                TextColumn::make('source_path')
                    ->label(__('admin.fields.source_path'))
                    ->limit(40)
                    ->toggleable(isToggledHiddenByDefault: true),
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
                TextColumn::make('created_at')
                    ->label(__('admin.fields.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label(__('admin.fields.updated_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
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
        /** @var FeedMappingProfile $profile */
        $profile = $this->getOwnerRecord();

        return collect($profile->sample_fields ?? [])
            ->pluck('path')
            ->filter()
            ->values()
            ->all();
    }

    private function createDraftMappings(): void
    {
        /** @var FeedMappingProfile $profile */
        $profile = $this->getOwnerRecord();
        $sampleFields = collect($profile->sample_fields ?? []);
        $existingCanonicalIds = $profile->fieldMappings()->pluck('canonical_field_id')->all();
        $sortOrder = (int) ($profile->fieldMappings()->max('sort_order') ?? 0);
        $created = 0;

        CanonicalField::query()
            ->active()
            ->whereNotIn('id', $existingCanonicalIds)
            ->orderBy('field_group')
            ->orderBy('sort_order')
            ->orderBy('label')
            ->get()
            ->each(function (CanonicalField $field) use ($profile, $sampleFields, &$sortOrder, &$created): void {
                $match = $this->guessSampleField($field, $sampleFields);

                $profile->fieldMappings()->create([
                    'canonical_field_id' => $field->id,
                    'mapping_action' => $match ? 'map' : 'skip',
                    'source_field' => $match['path'] ?? null,
                    'source_path' => $match['path'] ?? null,
                    'source_sample' => $match['sample'] ?? null,
                    'transform_type' => $this->defaultTransformForField($field),
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
     * @param  \Illuminate\Support\Collection<int, array<string, mixed>>  $sampleFields
     * @return array<string, mixed>|null
     */
    private function guessSampleField(CanonicalField $field, $sampleFields): ?array
    {
        $needles = collect([
            $field->key,
            $field->target_column,
            $field->label,
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
