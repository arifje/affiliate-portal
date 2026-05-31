<?php

namespace App\Filament\Resources\Users\Tables;

use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\SelectColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('admin.users.fields.name'))
                    ->searchable(),
                TextColumn::make('email')
                    ->label(__('admin.users.fields.email'))
                    ->searchable(),
                SelectColumn::make('admin_locale')
                    ->label(__('admin.users.fields.admin_locale'))
                    ->options(User::ADMIN_LOCALES)
                    ->sortable(),
                TextColumn::make('email_verified_at')
                    ->label(__('admin.users.fields.email_verified_at'))
                    ->dateTime()
                    ->sortable()
                    ->placeholder(__('admin.users.placeholders.not_verified')),
                IconColumn::make('is_active')
                    ->label(__('admin.users.fields.is_active'))
                    ->boolean()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label(__('admin.users.fields.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label(__('admin.users.fields.updated_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('email_verified_at')
                    ->label(__('admin.users.filters.email_verified'))
                    ->nullable(),
                TernaryFilter::make('is_active')
                    ->label(__('admin.users.fields.is_active')),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make()
                    ->visible(fn (User $record): bool => $record->id !== auth()->id()),
            ]);
    }
}
