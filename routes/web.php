<?php

use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

Route::get('/', [DashboardController::class, 'index'])->name('dashboard.index');
Route::get('/incidents/{incident}', [DashboardController::class, 'show'])->name('dashboard.show');
Route::patch('/incidents/{incident}', [DashboardController::class, 'update'])->name('dashboard.update');
Route::get('/recurring', [DashboardController::class, 'recurring'])->name('dashboard.recurring');

require __DIR__.'/settings.php';
