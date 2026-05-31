<?php

namespace App\Filament\Pages;

use App\Support\PlatformSettings;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use UnitEnum;

/**
 * @property-read Schema $form
 */
class Settings extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static ?int $navigationSort = 100;

    /**
     * @var array<string, mixed> | null
     */
    public ?array $data = [];

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('admin.navigation.administration');
    }

    public static function getNavigationLabel(): string
    {
        return __('admin.pages.settings.navigation_label');
    }

    public function getTitle(): string|Htmlable
    {
        return __('admin.pages.settings.title');
    }

    public function mount(): void
    {
        $this->form->fill([
            'website_is_online' => PlatformSettings::websiteIsOnline(),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('admin.pages.settings.sections.website_status'))
                    ->description(__('admin.pages.settings.descriptions.website_status'))
                    ->schema([
                        Toggle::make('website_is_online')
                            ->label(__('admin.pages.settings.fields.website_online'))
                            ->helperText(__('admin.pages.settings.help.website_online'))
                            ->default(true)
                            ->required(),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        PlatformSettings::setWebsiteIsOnline((bool) ($data['website_is_online'] ?? true));

        Notification::make()
            ->success()
            ->title(__('admin.pages.settings.notifications.saved'))
            ->send();
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                $this->getFormContentComponent(),
            ]);
    }

    public function getFormContentComponent(): Component
    {
        return Form::make([EmbeddedSchema::make('form')])
            ->id('form')
            ->livewireSubmitHandler('save')
            ->footer([
                Actions::make($this->getFormActions())
                    ->alignment($this->getFormActionsAlignment())
                    ->sticky($this->areFormActionsSticky())
                    ->key('form-actions'),
            ]);
    }

    /**
     * @return array<Action | ActionGroup>
     */
    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label(__('admin.pages.settings.actions.save'))
                ->submit('save')
                ->keyBindings(['mod+s']),
        ];
    }
}
