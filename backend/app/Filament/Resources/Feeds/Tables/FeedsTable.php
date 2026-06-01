<?php

namespace App\Filament\Resources\Feeds\Tables;

use App\Filament\Resources\Feeds\FeedResource;
use App\Models\Feed;
use App\Services\Feeds\FeedImporter;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Throwable;

class FeedsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->withCount([
                'products',
                'importBatches',
            ]))
            ->columns([
                TextColumn::make('name')
                    ->label(__('admin.fields.name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('site.name')
                    ->label(__('admin.fields.site'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('partner.name')
                    ->label(__('admin.fields.partner'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('provider')
                    ->label(__('admin.fields.platform'))
                    ->badge()
                    ->searchable(),
                TextColumn::make('source_type')
                    ->label(__('admin.fields.source_type'))
                    ->badge()
                    ->searchable(),
                TextColumn::make('source_format')
                    ->label(__('admin.fields.source_format'))
                    ->badge()
                    ->searchable(),
                TextColumn::make('unique_identifier_field')
                    ->label(__('admin.fields.unique_identifier_field'))
                    ->placeholder('external_id')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('products_count')
                    ->label(__('admin.fields.products'))
                    ->numeric()
                    ->sortable(),
                TextColumn::make('import_batches_count')
                    ->label(__('admin.fields.imports'))
                    ->numeric()
                    ->sortable(),
                TextColumn::make('last_import_status')
                    ->label(__('admin.fields.last_import_status'))
                    ->badge()
                    ->placeholder(__('admin.placeholders.never'))
                    ->searchable(),
                TextColumn::make('last_import_finished_at')
                    ->label(__('admin.fields.last_import_finished_at'))
                    ->dateTime()
                    ->sortable()
                    ->placeholder('-'),
                IconColumn::make('is_active')
                    ->label(__('admin.fields.is_active'))
                    ->boolean(),
                IconColumn::make('import_create_new')
                    ->label(__('admin.fields.import_create_new'))
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('import_update_existing')
                    ->label(__('admin.fields.import_update_existing'))
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),
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
                SelectFilter::make('site_id')
                    ->label(__('admin.fields.site'))
                    ->relationship('site', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('partner_id')
                    ->label(__('admin.fields.partner'))
                    ->relationship('partner', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('provider')
                    ->label(__('admin.fields.platform'))
                    ->options(__('admin.options.providers')),
                TernaryFilter::make('is_active')
                    ->label(__('admin.fields.is_active')),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                Action::make('runImport')
                    ->label(__('admin.actions.run_import'))
                    ->icon(Heroicon::OutlinedPlay)
                    ->requiresConfirmation()
                    ->modalDescription(__('admin.messages.run_feed_import'))
                    ->action(function (Feed $record): void {
                        try {
                            $batch = app(FeedImporter::class)->import($record);
                        } catch (Throwable $exception) {
                            Notification::make()
                                ->danger()
                                ->title(__('admin.messages.feed_import_failed'))
                                ->body($exception->getMessage())
                                ->send();

                            return;
                        }

                        Notification::make()
                            ->success()
                            ->title(__('admin.messages.feed_import_completed', [
                                'created' => $batch->created_rows,
                                'updated' => $batch->updated_rows,
                                'skipped' => $batch->skipped_rows,
                                'failed' => $batch->failed_rows,
                            ]))
                            ->send();
                    }),
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make()
                    ->modal(false)
                    ->requiresConfirmation(false)
                    ->extraAttributes(['wire:confirm' => __('admin.messages.deleting_feed')])
                    ->successRedirectUrl(FeedResource::getUrl()),
            ]);
    }
}
