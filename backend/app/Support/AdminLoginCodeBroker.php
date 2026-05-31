<?php

namespace App\Support;

use App\Models\AdminLoginCode;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AdminLoginCodeBroker
{
    public function __construct(private readonly TransactionalMailer $mailer)
    {
    }

    public function send(string $email, ?Request $request = null): bool
    {
        $email = $this->normalizeEmail($email);

        /** @var User|null $user */
        $user = User::query()
            ->whereRaw('lower(email) = ?', [$email])
            ->where('is_active', true)
            ->first();

        if (! $user) {
            return false;
        }

        AdminLoginCode::query()
            ->where('user_id', $user->id)
            ->whereNull('consumed_at')
            ->update(['consumed_at' => now()]);

        $code = $this->generateCode(PlatformSettings::loginCodeLength());
        $expiresAt = now()->addMinutes(PlatformSettings::loginCodeTtlMinutes());

        AdminLoginCode::query()->create([
            'user_id' => $user->id,
            'email' => $email,
            'code_hash' => Hash::make($code),
            'expires_at' => $expiresAt,
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
        ]);

        $this->mailer->sendLoginCode($user, $code, $expiresAt);

        return true;
    }

    public function consume(string $email, string $code): ?User
    {
        $email = $this->normalizeEmail($email);
        $code = trim($code);

        if ($email === '' || $code === '') {
            return null;
        }

        $loginCodes = AdminLoginCode::query()
            ->with('user')
            ->where('email', $email)
            ->whereNull('consumed_at')
            ->where('expires_at', '>', now())
            ->latest()
            ->limit(5)
            ->get();

        foreach ($loginCodes as $loginCode) {
            if (! Hash::check($code, $loginCode->code_hash)) {
                continue;
            }

            $loginCode->forceFill(['consumed_at' => now()])->save();

            if (! $loginCode->user?->is_active) {
                return null;
            }

            return $loginCode->user;
        }

        return null;
    }

    private function normalizeEmail(string $email): string
    {
        return Str::lower(trim($email));
    }

    private function generateCode(int $length): string
    {
        $code = '';

        for ($i = 0; $i < $length; $i++) {
            $code .= (string) random_int(0, 9);
        }

        return $code;
    }
}
