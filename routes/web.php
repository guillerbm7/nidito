<?php

use Illuminate\Support\Facades\Route;

Route::get('/session/user/{id}', function ($id) {
    $user = \App\Models\User::findOrFail($id);
    session(['selected_user_id' => $user->id]);
    return redirect()->route('dashboard');
})->name('session.user');

Route::livewire('/', 'pages::user-selector')->name('user.selector');

Route::middleware('user.selected')->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');

    Route::livewire('/calendario', 'pages::calendar')->name('calendario');
    Route::livewire('/peliculas', 'pages::movies')->name('peliculas');
    Route::livewire('/recetas', 'pages::recipes')->name('recetas');

});