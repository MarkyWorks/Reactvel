<?php

use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

Route::inertia('/', 'welcome', [
    'canRegister' => Features::enabled(Features::registration()),
])->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'dashboard')->name('dashboard');
    Route::get('audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');
    Route::resource('users', UserController::class)->except(['show']);
});

require __DIR__.'/settings.php';
