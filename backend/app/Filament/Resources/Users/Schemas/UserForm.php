<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\DateTimePicker;
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
                Section::make('User')
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('email')
                            ->label('Email address')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),
                        DateTimePicker::make('email_verified_at'),
                        Toggle::make('is_active')
                            ->required()
                            ->default(true),
                    ])
                    ->columns(2),
                Section::make('Password')
                    ->schema([
                        TextInput::make('password')
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
