<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Controller;

Route::get('/', function () {
    return 'hello';
});

Route::get('dk-log', [Controller::class, 'listFileLog']);
Route::get('dk-log/{filename}/{ext}', [Controller::class, 'showFileLog'])->name('dk-log.show');
