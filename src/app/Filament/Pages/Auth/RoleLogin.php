<?php

namespace App\Filament\Pages\Auth;

use Filament\Facades\Filament;
use Filament\Pages\Auth\Login;

class RoleLogin extends Login
{
    protected static string $view = 'filament.pages.auth.role-login';

    public function mount(): void
    {
        if (Filament::auth()->check()) {
            $panelId = Filament::auth()->user()?->role?->panelId();

            redirect()->to($panelId ? url('/' . $panelId) : url('/auth/login'));

            return;
        }

        $this->form->fill();
    }
}
