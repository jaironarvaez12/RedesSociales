<?php

use Illuminate\Support\Facades\Route;




 Route::group(['middleware' => 'auth'], function(){
Route::get('/',[App\Http\Controllers\InicioController::class, 'inicio'])->name('inicio');

        });


 
    Route::resource('auth','App\Http\Controllers\AuthController');
    Route::get('login', [App\Http\Controllers\AuthController::class, 'index'])->name('login');
    Route::post('logout', [App\Http\Controllers\AuthController::class, 'logout'])->name('logout');