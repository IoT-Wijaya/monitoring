<?php

namespace App\Filament\Pages\Auth;

use Filament\Auth\Pages\Login;
use Filament\Schemas\Schema;

class CustomLogin extends Login
{
    protected string $view = 'filament.pages.auth.custom-login';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                $this->getEmailFormComponent()->placeholder('johndoe@example.com'),
                $this->getPasswordFormComponent()->placeholder('Enter your password'),
                $this->getRememberFormComponent(),
            ])
            ->statePath('data');
    }
}
