<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\ReportRL34Controller;
use App\Http\Controllers\ReportRL35Controller;
use App\Http\Controllers\ReportPuskesadController;

// Auth Routes
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
Route::post('/register', [AuthController::class, 'register'])->name('register.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Protected Routes
Route::middleware(['auth'])->group(function () {
    Route::get('/', function () {
        return redirect()->route('dashboard');
    });

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Import Routes
    Route::get('/imports', [ImportController::class, 'index'])->name('imports.index');
    Route::post('/imports', [ImportController::class, 'store'])->name('imports.store');

    // RL 3.4 Report
    Route::get('/reports/rl34', [ReportRL34Controller::class, 'index'])->name('reports.rl34');
    Route::get('/reports/rl34/export', [ReportRL34Controller::class, 'exportExcel'])->name('reports.rl34.export');

    // RL 3.5 Report
    Route::get('/reports/rl35', [ReportRL35Controller::class, 'index'])->name('reports.rl35');
    Route::get('/reports/rl35/export', [ReportRL35Controller::class, 'exportExcel'])->name('reports.rl35.export');

    // Puskesad Report
    Route::get('/reports/puskesad', [ReportPuskesadController::class, 'index'])->name('reports.puskesad');
    Route::get('/reports/puskesad/export', [ReportPuskesadController::class, 'exportExcel'])->name('reports.puskesad.export');

    // Batch Zip Export (All Poli Excels)
    Route::get('/reports/export-zip', [ReportRL34Controller::class, 'exportZip'])->name('reports.export-zip');
});
