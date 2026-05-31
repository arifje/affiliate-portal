<?php

namespace App\Filament\Pages;

use App\Support\PlatformSettings;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
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
        $mailConnector = PlatformSettings::mailConnector();

        $this->form->fill([
            'website_is_online' => PlatformSettings::websiteIsOnline(),
            'admin_login_method' => PlatformSettings::adminLoginMethod(),
            'login_code_ttl_minutes' => PlatformSettings::loginCodeTtlMinutes(),
            'login_code_length' => PlatformSettings::loginCodeLength(),
            'mail_driver' => $mailConnector['driver'],
            'mail_from_name' => $mailConnector['from_name'],
            'mail_from_email' => $mailConnector['from_email'],
            'smtp_host' => $mailConnector['smtp_host'],
            'smtp_port' => $mailConnector['smtp_port'],
            'smtp_scheme' => $mailConnector['smtp_scheme'],
            'smtp_username' => $mailConnector['smtp_username'],
            'smtp_password' => $mailConnector['smtp_password'],
            'mail_api_key' => $mailConnector['api_key'],
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make(__('admin.pages.settings.tabs.settings'))
                    ->tabs([
                        Tab::make(__('admin.pages.settings.tabs.website'))
                            ->schema([
                                Section::make(__('admin.pages.settings.sections.website_status'))
                                    ->description(__('admin.pages.settings.descriptions.website_status'))
                                    ->schema([
                                        Toggle::make('website_is_online')
                                            ->label(__('admin.pages.settings.fields.website_online'))
                                            ->helperText(__('admin.pages.settings.help.website_online'))
                                            ->default(true)
                                            ->required(),
                                    ]),
                            ]),
                        Tab::make(__('admin.pages.settings.tabs.authentication'))
                            ->schema([
                                Section::make(__('admin.pages.settings.sections.admin_login'))
                                    ->description(__('admin.pages.settings.descriptions.admin_login'))
                                    ->schema([
                                        Select::make('admin_login_method')
                                            ->label(__('admin.pages.settings.fields.admin_login_method'))
                                            ->options(PlatformSettings::adminLoginMethods())
                                            ->helperText(__('admin.pages.settings.help.admin_login_method'))
                                            ->required()
                                            ->native(false),
                                        TextInput::make('login_code_ttl_minutes')
                                            ->label(__('admin.pages.settings.fields.login_code_ttl_minutes'))
                                            ->numeric()
                                            ->minValue(1)
                                            ->maxValue(60)
                                            ->required()
                                            ->helperText(__('admin.pages.settings.help.login_code_ttl_minutes')),
                                        TextInput::make('login_code_length')
                                            ->label(__('admin.pages.settings.fields.login_code_length'))
                                            ->numeric()
                                            ->minValue(6)
                                            ->maxValue(10)
                                            ->required()
                                            ->helperText(__('admin.pages.settings.help.login_code_length')),
                                    ])
                                    ->columns(3),
                            ]),
                        Tab::make(__('admin.pages.settings.tabs.email'))
                            ->schema([
                                Section::make(__('admin.pages.settings.sections.email_connector'))
                                    ->description(__('admin.pages.settings.descriptions.email_connector'))
                                    ->schema([
                                        Select::make('mail_driver')
                                            ->label(__('admin.pages.settings.fields.mail_driver'))
                                            ->options(PlatformSettings::mailDrivers())
                                            ->live()
                                            ->required()
                                            ->native(false),
                                        TextInput::make('mail_from_name')
                                            ->label(__('admin.pages.settings.fields.mail_from_name'))
                                            ->required()
                                            ->maxLength(255),
                                        TextInput::make('mail_from_email')
                                            ->label(__('admin.pages.settings.fields.mail_from_email'))
                                            ->email()
                                            ->required()
                                            ->maxLength(255),
                                        TextInput::make('smtp_host')
                                            ->label(__('admin.pages.settings.fields.smtp_host'))
                                            ->visible(fn (Get $get): bool => $get('mail_driver') === PlatformSettings::MAIL_DRIVER_SMTP)
                                            ->required(fn (Get $get): bool => $get('mail_driver') === PlatformSettings::MAIL_DRIVER_SMTP)
                                            ->maxLength(255),
                                        TextInput::make('smtp_port')
                                            ->label(__('admin.pages.settings.fields.smtp_port'))
                                            ->visible(fn (Get $get): bool => $get('mail_driver') === PlatformSettings::MAIL_DRIVER_SMTP)
                                            ->required(fn (Get $get): bool => $get('mail_driver') === PlatformSettings::MAIL_DRIVER_SMTP)
                                            ->numeric()
                                            ->minValue(1)
                                            ->maxValue(65535),
                                        Select::make('smtp_scheme')
                                            ->label(__('admin.pages.settings.fields.smtp_scheme'))
                                            ->visible(fn (Get $get): bool => $get('mail_driver') === PlatformSettings::MAIL_DRIVER_SMTP)
                                            ->options([
                                                '' => __('admin.pages.settings.options.smtp_schemes.default'),
                                                'smtp' => __('admin.pages.settings.options.smtp_schemes.smtp'),
                                                'smtps' => __('admin.pages.settings.options.smtp_schemes.smtps'),
                                            ])
                                            ->native(false),
                                        TextInput::make('smtp_username')
                                            ->label(__('admin.pages.settings.fields.smtp_username'))
                                            ->visible(fn (Get $get): bool => $get('mail_driver') === PlatformSettings::MAIL_DRIVER_SMTP)
                                            ->maxLength(255),
                                        TextInput::make('smtp_password')
                                            ->label(__('admin.pages.settings.fields.smtp_password'))
                                            ->visible(fn (Get $get): bool => $get('mail_driver') === PlatformSettings::MAIL_DRIVER_SMTP)
                                            ->password()
                                            ->revealable()
                                            ->maxLength(255),
                                        TextInput::make('mail_api_key')
                                            ->label(__('admin.pages.settings.fields.mail_api_key'))
                                            ->visible(fn (Get $get): bool => in_array($get('mail_driver'), [
                                                PlatformSettings::MAIL_DRIVER_MAILERSEND_API,
                                                PlatformSettings::MAIL_DRIVER_SENDGRID_API,
                                            ], true))
                                            ->required(fn (Get $get): bool => in_array($get('mail_driver'), [
                                                PlatformSettings::MAIL_DRIVER_MAILERSEND_API,
                                                PlatformSettings::MAIL_DRIVER_SENDGRID_API,
                                            ], true))
                                            ->password()
                                            ->revealable()
                                            ->maxLength(255),
                                    ])
                                    ->columns(3),
                            ]),
                    ])
                    ->persistTabInQueryString('settings-tab')
                    ->columnSpanFull(),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        PlatformSettings::setWebsiteIsOnline((bool) ($data['website_is_online'] ?? true));
        PlatformSettings::setAdminLoginMethod((string) ($data['admin_login_method'] ?? PlatformSettings::LOGIN_METHOD_PASSWORD));
        PlatformSettings::setLoginCodeTtlMinutes((int) ($data['login_code_ttl_minutes'] ?? 10));
        PlatformSettings::setLoginCodeLength((int) ($data['login_code_length'] ?? 6));
        PlatformSettings::setMailConnector([
            'driver' => $data['mail_driver'] ?? PlatformSettings::MAIL_DRIVER_LOG,
            'from_name' => $data['mail_from_name'] ?? null,
            'from_email' => $data['mail_from_email'] ?? null,
            'smtp_host' => $data['smtp_host'] ?? null,
            'smtp_port' => $data['smtp_port'] ?? null,
            'smtp_scheme' => $data['smtp_scheme'] ?? null,
            'smtp_username' => $data['smtp_username'] ?? null,
            'smtp_password' => $data['smtp_password'] ?? null,
            'api_key' => $data['mail_api_key'] ?? null,
        ]);

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
