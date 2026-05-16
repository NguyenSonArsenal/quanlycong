<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Controller;
use App\Http\Controllers\StoreController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\KpiController;
use App\Http\Controllers\DailyWorkController;
use App\Http\Controllers\PayrollController;

Route::get('/', function () { return redirect('/staff-shift-kpi/login'); });

Route::get('dk-log', [Controller::class, 'listFileLog']);
Route::get('dk-log/{filename}/{ext}', [Controller::class, 'showFileLog'])->name('dk-log.show');

Route::prefix('staff-shift-kpi')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::middleware('auth')->group(function () {
        // Daily Work
        Route::get('/daily', [DailyWorkController::class, 'index'])->name('daily.index');
        Route::post('/daily/update', [DailyWorkController::class, 'updateField'])->name('daily.update');
        Route::post('/daily/equalize', [DailyWorkController::class, 'equalize'])->name('daily.equalize');
        Route::post('/daily/lock', [DailyWorkController::class, 'lock'])->name('daily.lock');
        Route::delete('/daily/records/{userId}', [DailyWorkController::class, 'deleteRecord'])->name('daily.delete');

        // Payroll
        Route::get('/payrolls', [PayrollController::class, 'index'])->name('payrolls.index');

        // Stores
        Route::get('/stores', [StoreController::class, 'index'])->name('stores.index');
        Route::post('/stores', [StoreController::class, 'store'])->name('stores.store');
        Route::delete('/stores/{store}', [StoreController::class, 'destroy'])->name('stores.destroy');

        // Users
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::patch('/users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');

        // KPI Config
        Route::get('/kpi-config', [KpiController::class, 'index'])->name('kpi-config.index');
        Route::post('/kpi-config', [KpiController::class, 'store'])->name('kpi-config.store');
        Route::get('/kpi-config/{id}', [KpiController::class, 'show'])->name('kpi-config.show');
        Route::patch('/kpi-config/{id}', [KpiController::class, 'update'])->name('kpi-config.update');
        Route::delete('/kpi-config/{id}', [KpiController::class, 'destroy'])->name('kpi-config.destroy');
        Route::post('/kpi-config/{id}/matrix', [KpiController::class, 'updateMatrix'])->name('kpi-config.update-matrix');
        Route::post('/kpi-config/{id}/regenerate', [KpiController::class, 'regenerate'])->name('kpi-config.regenerate');
    });
});
