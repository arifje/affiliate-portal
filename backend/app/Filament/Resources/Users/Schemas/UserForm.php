<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Models\User;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('admin.users.sections.user'))
                    ->schema([
                        TextInput::make('name')
                            ->label(__('admin.users.fields.name'))
                            ->required()
                            ->maxLength(255),
                        TextInput::make('email')
                            ->label(__('admin.users.fields.email'))
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),
                        Select::make('admin_locale')
                            ->label(__('admin.users.fields.admin_locale'))
                            ->options(User::ADMIN_LOCALES)
                            ->default('en')
                            ->required()
                            ->native(false),
                        DateTimePicker::make('email_verified_at')
                            ->label(__('admin.users.fields.email_verified_at')),
                        Toggle::make('is_active')
                            ->label(__('admin.users.fields.is_active'))
                            ->required()
                            ->default(true),
                    ])
                    ->columns(2),
                Section::make(__('admin.users.sections.password'))
                    ->schema([
                        TextInput::make('password')
                            ->label(__('admin.users.fields.password'))
                            ->password()
                            ->revealable()
                            ->autocomplete('new-password')
                            ->formatStateUsing(fn (): ?string => null)
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->dehydrateStateUsing(fn (?string $state): ?string => filled($state) ? $state : null)
                            ->dehydrated(fn (?string $state): bool => filled($state))
                            ->minLength(12)
                            ->maxLength(255),
                    ]),
            ]);
    }
}
