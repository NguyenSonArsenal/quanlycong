<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Controller;
use App\Http\Controllers\StoreController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\KpiController;
use App\Http\Controllers\DailyWorkController;
use App\Http\Controllers\PayrollController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\MonthlyController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\SettingsController;

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

        // Monthly Dashboard
        Route::get('/monthly', [MonthlyController::class, 'index'])->name('monthly.index');
        Route::get('/monthly/{store}/revenue', [MonthlyController::class, 'revenue'])->name('monthly.revenue');
        Route::get('/monthly/{store}/calendar', [MonthlyController::class, 'calendar'])->name('monthly.calendar');
        Route::get('/monthly/{store}', [MonthlyController::class, 'show'])->name('monthly.show');

        // Payroll
        Route::get('/payrolls', [PayrollController::class, 'index'])->name('payrolls.index');

        // My Profile
        Route::get('/my-profile', [ProfileController::class, 'index'])->name('profile');

        // Stores
        Route::get('/stores', [StoreController::class, 'index'])->name('stores.index');
        Route::post('/stores', [StoreController::class, 'store'])->name('stores.store');
        Route::delete('/stores/{store}', [StoreController::class, 'destroy'])->name('stores.destroy');

        // Staff (CRUD) — /staff thay vì /users theo spec
        Route::get('/staff', [UserController::class, 'index'])->name('users.index');
        Route::get('/staff/create', [UserController::class, 'create'])->name('users.create');
        Route::post('/staff', [UserController::class, 'store'])->name('users.store');
        Route::get('/staff/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
        Route::put('/staff/{user}', [UserController::class, 'update'])->name('users.update');
        Route::delete('/staff/{user}', [UserController::class, 'destroy'])->name('users.destroy');

        // KPI Config
        Route::get('/kpi-config', [KpiController::class, 'index'])->name('kpi-config.index');
        Route::post('/kpi-config', [KpiController::class, 'store'])->name('kpi-config.store');
        Route::get('/kpi-config/{id}', [KpiController::class, 'show'])->name('kpi-config.show');
        Route::patch('/kpi-config/{id}', [KpiController::class, 'update'])->name('kpi-config.update');
        Route::delete('/kpi-config/{id}', [KpiController::class, 'destroy'])->name('kpi-config.destroy');
        Route::post('/kpi-config/{id}/matrix', [KpiController::class, 'updateMatrix'])->name('kpi-config.update-matrix');
        Route::post('/kpi-config/{id}/regenerate', [KpiController::class, 'regenerate'])->name('kpi-config.regenerate');

        // ── Admin: Phân quyền ──
        Route::get('/admin/permissions', [PermissionController::class, 'index'])->name('admin.permissions');
        Route::get('/admin/permissions/{role}', [PermissionController::class, 'show'])->name('admin.permissions.show');
        Route::post('/admin/permissions/{role}', [PermissionController::class, 'update'])->name('admin.permissions.update');
        Route::get('/admin/permissions/{role}/reset', [PermissionController::class, 'resetDefault'])->name('admin.permissions.reset');

        // ── Settings (Cài đặt catalog) ──
        Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
        Route::put('/settings/positions/{position}', [SettingsController::class, 'updatePosition'])->name('settings.positions.update');
        Route::put('/settings/brackets/{id}', [SettingsController::class, 'updateBracket'])->name('settings.brackets.update');
        Route::post('/settings/brackets', [SettingsController::class, 'storeBracket'])->name('settings.brackets.store');
        Route::delete('/settings/brackets/{id}', [SettingsController::class, 'destroyBracket'])->name('settings.brackets.destroy');
        Route::delete('/settings/brackets/group/{position_code}/{contract_type}', [SettingsController::class, 'destroyGroup'])->name('settings.brackets.destroy_group');
    });
});
