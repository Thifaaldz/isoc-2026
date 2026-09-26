<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome');

// Pintasan: /dashboard mengarahkan pengguna ke panel sesuai rolenya.
Route::get('/dashboard', function () {
    $user = auth()->user();

    return $user ? redirect('/' . $user->role->panelId()) : redirect('/');
});
