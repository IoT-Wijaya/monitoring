<?php

namespace App\Filament\Pages\Auth;

use Filament\Auth\Pages\Register;
use Filament\Schemas\Schema;

class CustomRegister extends Register
{
    protected string $view = 'filament.pages.auth.custom-register';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                $this->getNameFormComponent()->placeholder('Enter your full name'),
                $this->getEmailFormComponent()->placeholder('Enter your email address'),
                $this->getPasswordFormComponent()->placeholder('Create a password'),
                $this->getPasswordConfirmationFormComponent()->placeholder('Confirm your password'),
            ])
            ->statePath('data');
    }
}
