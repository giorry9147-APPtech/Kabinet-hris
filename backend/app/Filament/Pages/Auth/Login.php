<?php

namespace App\Filament\Pages\Auth;

use Filament\Pages\Auth\Login as BaseLogin;

class Login extends BaseLogin
{
    protected static string $view = 'filament.pages.auth.login';

    public function getHeading(): string
    {
        return '';
    }

    public function getSubHeading(): ?string
    {
        return null;
    }

    public function hasLogo(): bool
    {
        return false;
    }
}
