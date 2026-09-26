<?php

namespace App\Livewire;

use App\Enums\UserRole;
use Livewire\Component;

/** Komponen Livewire di halaman depan: pilih/arahkan ke panel sesuai role. */
class PanelPicker extends Component
{
    public function render()
    {
        $user = auth()->user();

        return view('livewire.panel-picker', [
            'panels' => collect(UserRole::cases())->map(fn (UserRole $r) => [
                'label' => $r->label(),
                'url' => url('/' . $r->panelId()),
                'mine' => $user?->role === $r,
            ]),
        ]);
    }
}
