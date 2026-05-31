<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Models\User;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class UserInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('name')
                    ->label(__('admin.users.fields.name')),
                TextEntry::make('email')
                    ->label(__('admin.users.fields.email')),
                TextEntry::make('admin_locale')
                    ->label(__('admin.users.fields.admin_locale'))
                    ->formatStateUsing(fn (?string $state): string => $state ? (User::ADMIN_LOCALES[$state] ?? $state) : '-'),
                TextEntry::make('email_verified_at')
                    ->label(__('admin.users.fields.email_verified_at'))
                    ->dateTime()
                    ->placeholder('-'),
                IconEntry::make('is_active')
                    ->label(__('admin.users.fields.is_active'))
                    ->boolean(),
                TextEntry::make('created_at')
                    ->label(__('admin.users.fields.created_at'))
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->label(__('admin.users.fields.updated_at'))
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
