<?php

use Illuminate\Support\Facades\Route;

// Rotas para as páginas de autenticação
Route::get('/register', function () {
    return view('auth.register');
})->name('register.show');

Route::get('/login', function () {
    return view('auth.login');
})->name('login.show');

// Rota inicial - redireciona para login ou registro
Route::get('/', function () {
    return redirect()->route('login.show');
});

Route::get('/wallet/dashboard', function () {
    return view('wallet.dashboard');
})->name('wallet.dashboard.show');
