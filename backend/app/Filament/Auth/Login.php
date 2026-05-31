<?php

namespace App\Filament\Auth;

use App\Support\AdminLoginCodeBroker;
use App\Support\PlatformSettings;
use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Auth\Http\Responses\Contracts\LoginResponse;
use Filament\Auth\Pages\Login as BaseLogin;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Models\Contracts\FilamentUser;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Validator;
use Throwable;

class Login extends BaseLogin
{
    public function form(Schema $schema): Schema
    {
        $components = [
            $this->getEmailFormComponent(),
        ];

        if ($this->passwordEnabled()) {
            $components[] = $this->getPasswordFormComponent();
        }

        if ($this->emailCodeEnabled()) {
            $components[] = $this->getCodeFormComponent();
        }

        $components[] = $this->getRememberFormComponent();

        return $schema->components($components);
    }

    public function authenticate(): ?LoginResponse
    {
        if ($this->passwordOnly()) {
            return parent::authenticate();
        }

        $data = $this->form->getState();

        if ($this->emailCodeEnabled() && filled($data['code'] ?? null)) {
            try {
                $this->rateLimit(5);
            } catch (TooManyRequestsException $exception) {
                $this->getRateLimitedNotification($exception)?->send();

                return null;
            }

            $user = app(AdminLoginCodeBroker::class)->consume(
                (string) ($data['email'] ?? ''),
                (string) ($data['code'] ?? ''),
            );

            if (! $user) {
                $this->throwFailureValidationException();
            }

            if ($user instanceof FilamentUser && (! $user->canAccessPanel(Filament::getCurrentOrDefaultPanel()))) {
                $this->throwFailureValidationException();
            }

            Filament::auth()->login($user, (bool) ($data['remember'] ?? false));

            session()->regenerate();

            return app(LoginResponse::class);
        }

        if ($this->passwordEnabled() && filled($data['password'] ?? null)) {
            return parent::authenticate();
        }

        $this->throwFailureValidationException();
    }

    public function sendLoginCode(AdminLoginCodeBroker $broker): void
    {
        try {
            $this->rateLimit(3, 60, 'sendLoginCode');
        } catch (TooManyRequestsException $exception) {
            $this->getRateLimitedNotification($exception)?->send();

            return;
        }

        Validator::make([
            'data' => [
                'email' => $this->data['email'] ?? null,
            ],
        ], [
            'data.email' => ['required', 'email'],
        ])->validate();

        try {
            $broker->send((string) $this->data['email'], request());
        } catch (Throwable $exception) {
            report($exception);

            Notification::make()
                ->danger()
                ->title(__('admin.auth.login_code.notifications.failed'))
                ->send();

            return;
        }

        Notification::make()
            ->success()
            ->title(__('admin.auth.login_code.notifications.sent'))
            ->body(__('admin.auth.login_code.notifications.sent_body'))
            ->send();
    }

    protected function getPasswordFormComponent(): Component
    {
        return parent::getPasswordFormComponent()
            ->required($this->passwordOnly());
    }

    protected function getCodeFormComponent(): Component
    {
        return TextInput::make('code')
            ->label(__('admin.auth.login_code.form.code'))
            ->helperText(__('admin.auth.login_code.form.code_help'))
            ->autocomplete('one-time-code')
            ->inputMode('numeric')
            ->length(PlatformSettings::loginCodeLength())
            ->rule('digits:' . PlatformSettings::loginCodeLength())
            ->required($this->emailCodeOnly());
    }

    /**
     * @return array<Action | ActionGroup>
     */
    protected function getFormActions(): array
    {
        if (! $this->emailCodeEnabled()) {
            return parent::getFormActions();
        }

        return [
            Action::make('sendLoginCode')
                ->label(__('admin.auth.login_code.actions.send_code'))
                ->color('gray')
                ->action('sendLoginCode'),
            $this->getAuthenticateFormAction()
                ->label(__('admin.auth.login_code.actions.authenticate')),
        ];
    }

    public function getSubheading(): string|Htmlable|null
    {
        if ($this->emailCodeOnly()) {
            return __('admin.auth.login_code.subheading');
        }

        if (PlatformSettings::adminLoginMethod() === PlatformSettings::LOGIN_METHOD_PASSWORD_OR_EMAIL_CODE) {
            return __('admin.auth.login_code.mixed_subheading');
        }

        return parent::getSubheading();
    }

    private function passwordOnly(): bool
    {
        return PlatformSettings::adminLoginMethod() === PlatformSettings::LOGIN_METHOD_PASSWORD;
    }

    private function emailCodeOnly(): bool
    {
        return PlatformSettings::adminLoginMethod() === PlatformSettings::LOGIN_METHOD_EMAIL_CODE;
    }

    private function passwordEnabled(): bool
    {
        return in_array(PlatformSettings::adminLoginMethod(), [
            PlatformSettings::LOGIN_METHOD_PASSWORD,
            PlatformSettings::LOGIN_METHOD_PASSWORD_OR_EMAIL_CODE,
        ], true);
    }

    private function emailCodeEnabled(): bool
    {
        return in_array(PlatformSettings::adminLoginMethod(), [
            PlatformSettings::LOGIN_METHOD_EMAIL_CODE,
            PlatformSettings::LOGIN_METHOD_PASSWORD_OR_EMAIL_CODE,
        ], true);
    }
}
