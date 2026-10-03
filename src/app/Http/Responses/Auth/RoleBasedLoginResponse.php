<?php

namespace App\Http\Responses\Auth;

use Filament\Facades\Filament;
use Filament\Http\Responses\Auth\Contracts\LoginResponse as LoginResponseContract;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Livewire\Features\SupportRedirects\Redirector;

class RoleBasedLoginResponse implements LoginResponseContract
{
    private const PANEL_PATHS = ['superadmin', 'admin', 'tutor', 'peserta', 'auth'];

    public function toResponse($request): RedirectResponse | Redirector
    {
        $user = Filament::auth()->user();
        $panelId = $user?->role?->panelId();
        $panelUrl = $panelId ? url('/' . $panelId) : url('/auth/login');
        $intended = session()->pull('url.intended');

        // Kembalikan user ke halaman yang tadi diminta (mis. link sertifikat atau halaman event),
        // kecuali halaman itu milik panel role lain.
        if ($this->isAllowedIntendedUrl($intended, $panelId)) {
            return redirect()->to($intended);
        }

        return redirect()->to($panelUrl);
    }

    private function isAllowedIntendedUrl(?string $url, ?string $panelId): bool
    {
        if (blank($url) || ! Str::startsWith($url, url('/'))) {
            return false;
        }

        $firstSegment = Str::before(ltrim((string) parse_url($url, PHP_URL_PATH), '/'), '/');

        return ! in_array($firstSegment, self::PANEL_PATHS, true) || $firstSegment === $panelId;
    }
}
