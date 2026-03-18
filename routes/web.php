<?php

use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UsersExportController;
use App\Http\Controllers\UsersImportController;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

Route::inertia('/', 'welcome', [
    'canRegister' => Features::enabled(Features::registration()),
])->name('home');

Broadcast::routes(['middleware' => ['auth']]);

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');
    Route::get('users/import', [UsersImportController::class, 'create'])->name('users.import.create');
    Route::post('users/import', [UsersImportController::class, 'store'])->name('users.import.store');
    Route::get('users/import/{userImport}/status', [UsersImportController::class, 'status'])->name('users.import.status');
    Route::get('users/export', [UsersExportController::class, 'create'])->name('users.export.create');
    Route::post('users/export', [UsersExportController::class, 'store'])->name('users.export.store');
    Route::get('users/export/{userExport}', [UsersExportController::class, 'download'])->name('users.export.download');
    Route::get('users/export/{userExport}/status', [UsersExportController::class, 'status'])->name('users.export.status');
    Route::resource('users', UserController::class)->except(['show']);
});

require __DIR__.'/settings.php';
