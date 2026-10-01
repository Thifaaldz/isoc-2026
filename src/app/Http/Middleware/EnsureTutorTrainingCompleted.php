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
            'filament.tutor.auth.login',
            'filament.tutor.auth.logout',
        ], true)) {
            return $next($request);
        }

        if ($request->is('livewire/update') && str_contains((string) $request->headers->get('referer'), '/tutor/pelatihan-tutor')) {
            return $next($request);
        }

        return redirect()->to(url('/tutor/pelatihan-tutor'));
    }
}
