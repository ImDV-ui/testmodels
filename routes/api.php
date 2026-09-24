<?php

use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/user/get', [UserController::class, 'get'])->name('user.get');
Route::get('/user', [UserController::class, 'get'])->name('user.list');

Route::post('/user/create', [UserController::class, 'create'])->name('user.create');
Route::post('/user/login', [UserController::class, 'login'])->name('user.login');

Route::match(['put', 'patch', 'post'], '/user/update_username', [UserController::class, 'updateUsername'])->name('user.update_username');
Route::match(['put', 'patch', 'post'], '/user/update-username', [UserController::class, 'updateUsername']);

Route::match(['put', 'patch', 'post'], '/user/update_email', [UserController::class, 'updateEmail'])->name('user.update_email');
Route::match(['put', 'patch', 'post'], '/user/update-email', [UserController::class, 'updateEmail']);

Route::match(['put', 'patch', 'post'], '/user/update_password', [UserController::class, 'updatePassword'])->name('user.update_password');
Route::match(['put', 'patch', 'post'], '/user/update-password', [UserController::class, 'updatePassword']);

Route::match(['delete', 'post'], '/user/delete', [UserController::class, 'delete'])->name('user.delete');
