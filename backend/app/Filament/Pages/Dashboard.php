<?php

namespace App\Filament\Pages;

use App\Models\Site;
use Filament\Forms\Components\Select;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;

class Dashboard extends BaseDashboard
{
    use HasFiltersForm;

    public static function getNavigationLabel(): string
    {
        return __('admin.pages.dashboard.navigation_label');
    }

    public function getTitle(): string|Htmlable
    {
        return __('admin.pages.dashboard.title');
    }

    public function filtersForm(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('site_id')
                    ->label(__('admin.pages.dashboard.filters.site'))
                    ->placeholder(__('admin.pages.dashboard.filters.all_sites'))
                    ->options(fn (): array => Site::query()
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->all())
                    ->searchable()
                    ->preload(),
            ])
            ->columns(1);
    }
}
