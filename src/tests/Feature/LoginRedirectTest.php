<?php

use App\Enums\UserRole;
use App\Filament\Pages\Auth\RoleLogin;
use App\Models\User;
use Filament\Facades\Filament;
use Livewire\Livewire;

test('login mengembalikan user ke halaman yang diminta sebelumnya', function () {
    User::query()->create([
        'name' => 'Peserta', 'email' => 'p@isoc.id', 'password' => 'password',
        'role' => UserRole::Peserta, 'is_active' => true,
    ]);
    Filament::setCurrentPanel(Filament::getPanel('auth'));
    session()->put('url.intended', url('/events/event-uji/register'));

    Livewire::test(RoleLogin::class)
        ->set('data.email', 'p@isoc.id')
        ->set('data.password', 'password')
        ->call('authenticate')
        ->assertRedirect(url('/events/event-uji/register'));
});

test('login tidak mengarahkan user ke panel role lain', function () {
    User::query()->create([
        'name' => 'Peserta', 'email' => 'p@isoc.id', 'password' => 'password',
        'role' => UserRole::Peserta, 'is_active' => true,
    ]);
    Filament::setCurrentPanel(Filament::getPanel('auth'));
    session()->put('url.intended', url('/superadmin'));

    Livewire::test(RoleLogin::class)
        ->set('data.email', 'p@isoc.id')
        ->set('data.password', 'password')
        ->call('authenticate')
        ->assertRedirect(url('/peserta'));
});
