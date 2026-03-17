<?php

use App\Livewire\UserSelector;
//use App\Http\Controllers\SessionController;
use Illuminate\Support\Facades\Route;


// Selector de usuario — sin middleware, es la puerta de entrada

Route::get('/', UserSelector::class)->name('user.selector');


// Guardar usuario en sesión
//Route::post('/session/user', [SessionController::class, 'store'])->name('session.user');

// Rutas protegidas — todo lo que venga después irá aquí dentro
Route::middleware('user.selected')->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');
});

Route::get('/session/user/{id}', function ($id) {
    $user = \App\Models\User::findOrFail($id);
    session(['selected_user_id' => $user->id]);
    return redirect()->route('dashboard');
})->name('session.user');

//require __DIR__.'/settings.php';
