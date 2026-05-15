<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Controller;
use App\Http\Controllers\StoreController;
use App\Http\Controllers\UserController;

Route::get('/', function () { return redirect('/staff-shift-kpi/login'); });

Route::get('dk-log', [Controller::class, 'listFileLog']);
Route::get('dk-log/{filename}/{ext}', [Controller::class, 'showFileLog'])->name('dk-log.show');

// Cụm Auth
Route::prefix('staff-shift-kpi')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/logout', [AuthController::class, 'logout'])->name('logout');

    // Các trang cần login mới vào được
    Route::middleware('auth')->group(function () {
        Route::get('/daily', function() { return view('dashboard'); });

        // Quản lý Cửa hàng
        Route::resource('stores', StoreController::class)->only(['index', 'store', 'destroy']);

        // Quản lý Nhân sự
        Route::resource('users', UserController::class)->only(['index', 'store', 'destroy']);
    });
});
