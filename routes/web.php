<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CacheController;
use App\Http\Controllers\ConsultaController;
use App\Http\Controllers\PasswordController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.store');

Route::middleware('auth')->group(function () {
    Route::get('/', fn () => redirect()->route('consulta'))->name('home');
    Route::get('/consulta', [ConsultaController::class, 'index'])->name('consulta');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/senha', [PasswordController::class, 'edit'])->name('senha.edit');
    Route::post('/senha', [PasswordController::class, 'update'])->name('senha.update');

    Route::middleware('super')->group(function () {
        Route::get('/usuarios', [UserController::class, 'index'])->name('usuarios.index');
        Route::get('/usuarios/novo', [UserController::class, 'create'])->name('usuarios.create');
        Route::post('/usuarios', [UserController::class, 'store'])->name('usuarios.store');
        Route::get('/usuarios/{usuario}/editar', [UserController::class, 'edit'])->name('usuarios.edit');
        Route::put('/usuarios/{usuario}', [UserController::class, 'update'])->name('usuarios.update');

        Route::get('/cache', [CacheController::class, 'index'])->name('cache.index');
        Route::post('/cache', [CacheController::class, 'store'])->name('cache.store');
    });
});
