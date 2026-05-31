<?php

namespace App\Filament\Pages;

use App\Models\User;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
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
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use UnitEnum;

/**
 * @property-read Schema $form
 */
class Profile extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserCircle;

    protected static ?int $navigationSort = 95;

    protected static ?string $slug = 'profile';

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
        return __('admin.pages.profile.navigation_label');
    }

    public function getTitle(): string|Htmlable
    {
        return __('admin.pages.profile.title');
    }

    public function mount(): void
    {
        /** @var User $user */
        $user = Filament::auth()->user();

        $this->form->fill([
            'name' => $user->name,
            'email' => $user->email,
            'admin_locale' => $user->admin_locale,
            'current_password' => null,
            'password' => null,
            'password_confirmation' => null,
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('admin.pages.profile.sections.profile'))
                    ->description(__('admin.pages.profile.descriptions.profile'))
                    ->schema([
                        TextInput::make('name')
                            ->label(__('admin.fields.name'))
                            ->required()
                            ->maxLength(255),
                        TextInput::make('email')
                            ->label(__('admin.fields.email'))
                            ->email()
                            ->required()
                            ->rule(fn () => Rule::unique(User::class, 'email')->ignore(Filament::auth()->id()))
                            ->maxLength(255),
                        Select::make('admin_locale')
                            ->label(__('admin.resources.users.fields.admin_locale'))
                            ->options(User::ADMIN_LOCALES)
                            ->required()
                            ->native(false),
                    ])
                    ->columns(2),
                Section::make(__('admin.pages.profile.sections.password'))
                    ->description(__('admin.pages.profile.descriptions.password'))
                    ->schema([
                        TextInput::make('current_password')
                            ->label(__('admin.pages.profile.fields.current_password'))
                            ->password()
                            ->revealable()
                            ->autocomplete('current-password')
                            ->requiredWith('password')
                            ->maxLength(255),
                        TextInput::make('password')
                            ->label(__('admin.pages.profile.fields.new_password'))
                            ->password()
                            ->revealable()
                            ->autocomplete('new-password')
                            ->confirmed()
                            ->minLength(12)
                            ->maxLength(255),
                        TextInput::make('password_confirmation')
                            ->label(__('admin.pages.profile.fields.confirm_password'))
                            ->password()
                            ->revealable()
                            ->autocomplete('new-password')
                            ->requiredWith('password')
                            ->maxLength(255),
                    ])
                    ->columns(3),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        /** @var User $user */
        $user = Filament::auth()->user();

        if (filled($data['password'] ?? null) && (! Hash::check((string) ($data['current_password'] ?? ''), $user->password))) {
            $this->addError('data.current_password', __('admin.pages.profile.validation.current_password'));

            return;
        }

        $user->fill([
            'name' => $data['name'],
            'email' => $data['email'],
            'admin_locale' => $data['admin_locale'],
        ]);

        if (filled($data['password'] ?? null)) {
            $user->password = $data['password'];
        }

        $user->save();

        $this->form->fill([
            ...$data,
            'current_password' => null,
            'password' => null,
            'password_confirmation' => null,
        ]);

        Notification::make()
            ->success()
            ->title(__('admin.pages.profile.notifications.saved'))
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
                ->label(__('admin.pages.profile.actions.save'))
                ->submit('save')
                ->keyBindings(['mod+s']),
        ];
    }
}
