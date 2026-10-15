<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTutorTrainingCompleted
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || $user->role !== UserRole::Tutor || (bool) $user->tutor?->tot_completed) {
            return $next($request);
        }

        $routeName = $request->route()?->getName();

        if (in_array($routeName, [
            'filament.tutor.pages.pelatihan-tutor',
            // Preview materi tetap bisa dibuka tutor yang sedang menjalani ToT.
            'filament.tutor.pages.preview-materi',
            // Tutor bisa mendaftar event dulu lalu mengerjakan ToT untuk event tersebut.
            'filament.tutor.pages.daftar-event',
            // Sertifikat tutor bisa dicetak tanpa syarat ToT.
            'filament.tutor.pages.sertifikat-tutor',
            'filament.tutor.auth.login',
            'filament.tutor.auth.logout',
        ], true)) {
            return $next($request);
        }

        $referer = (string) $request->headers->get('referer');

        if ($request->is('livewire/update') && (str_contains($referer, '/tutor/pelatihan-tutor') || str_contains($referer, '/tutor/preview-materi') || str_contains($referer, '/tutor/daftar-event') || str_contains($referer, '/tutor/sertifikat-tutor'))) {
            return $next($request);
        }

        return redirect()->to(url('/tutor/pelatihan-tutor'));
    }
}
