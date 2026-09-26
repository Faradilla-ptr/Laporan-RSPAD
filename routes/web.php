<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\ReportPuskesadController;
use App\Http\Controllers\ReportRL34Controller;
use App\Http\Controllers\ReportRL35Controller;
use Illuminate\Support\Facades\Route;

// Auth Routes with Brute Force Protection (Throttle 5 attempts per minute)
Route::middleware(['throttle:5,1'])->group(function () {
    Route::post('/login', [AuthController::class, 'login'])->name('login.post');
    Route::post('/register', [AuthController::class, 'register'])->name('register.post');
});

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Protected Routes
Route::middleware(['auth'])->group(function () {
    Route::get('/', function () {
        return redirect()->route('dashboard');
    });

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Import Routes (Rate limited to prevent resource exhaustion / DoS)
    Route::get('/imports', [ImportController::class, 'index'])->name('imports.index');
    Route::post('/imports', [ImportController::class, 'store'])->middleware('throttle:10,1')->name('imports.store');
    Route::delete('/imports/all', [ImportController::class, 'truncateAll'])->middleware('throttle:5,1')->name('imports.truncateAll');
    Route::delete('/imports/{id}', [ImportController::class, 'destroy'])->name('imports.destroy');

    // RL 3.4 Report
    Route::get('/reports/rl34', [ReportRL34Controller::class, 'index'])->name('reports.rl34');
    Route::get('/reports/rl34/export', [ReportRL34Controller::class, 'exportExcel'])->name('reports.rl34.export');
    Route::put('/reports/rl34', [ReportRL34Controller::class, 'update'])->middleware('throttle:30,1')->name('reports.rl34.update');
    Route::delete('/reports/rl34', [ReportRL34Controller::class, 'destroy'])->middleware('throttle:10,1')->name('reports.rl34.destroy');

    // RL 3.5 Report
    Route::get('/reports/rl35', [ReportRL35Controller::class, 'index'])->name('reports.rl35');
    Route::get('/reports/rl35/export', [ReportRL35Controller::class, 'exportExcel'])->name('reports.rl35.export');
    Route::put('/reports/rl35', [ReportRL35Controller::class, 'update'])->middleware('throttle:30,1')->name('reports.rl35.update');
    Route::delete('/reports/rl35', [ReportRL35Controller::class, 'destroy'])->middleware('throttle:10,1')->name('reports.rl35.destroy');

    // Puskesad Report
    Route::get('/reports/puskesad', [ReportPuskesadController::class, 'index'])->name('reports.puskesad');
    Route::get('/reports/puskesad/export', [ReportPuskesadController::class, 'exportExcel'])->name('reports.puskesad.export');
});
