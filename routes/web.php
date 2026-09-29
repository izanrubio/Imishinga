<?php

use App\Livewire\Auth\Login;
use App\Livewire\Boards\Index as BoardsIndex;
use App\Livewire\Boards\Show as BoardsShow;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('boards.index');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', Login::class)->name('login');
});

Route::post('/logout', function () {
    Auth::logout();

    request()->session()->invalidate();
    request()->session()->regenerateToken();

    return redirect()->route('login');
})->middleware('auth')->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/boards', BoardsIndex::class)->name('boards.index');
    Route::get('/boards/{board}', BoardsShow::class)->name('boards.show');
});
