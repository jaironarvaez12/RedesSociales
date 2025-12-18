<?php

use Illuminate\Support\Facades\Route;




Route::group(['middleware' => 'auth'], function(){
    Route::get('/',[App\Http\Controllers\InicioController::class, 'inicio'])->name('inicio');

    //Usuarios
       Route::resource('usuarios','App\Http\Controllers\UsuariosController');
     //PERMISOS
      Route::resource('permisos','App\Http\Controllers\PermisosController');
      Route::resource('roles','App\Http\Controllers\RolesController');
    //DOMINIOS
     Route::resource('dominios','App\Http\Controllers\DominiosController');
     Route::get('dominioscrearcontenido/{id_dominio}',[App\Http\Controllers\DominiosController::class, 'CrearContenido']) ->name('dominioscrearcontenido');
     Route::post('dominiotipogenerador/{id_dominio}',[App\Http\Controllers\DominiosController::class, 'GeneradorContenido']) ->name('dominiotipogenerador');
    Route::get('/dominios/{id}/wp', [App\Http\Controllers\DominiosController::class, 'verWp'])->name('dominios.wp');
    Route::post('generador/{id_dominio}', [App\Http\Controllers\DominiosController::class, 'Generador'])->name('generador');
    Route::get('dominioscontenido-generado/{id_dominio}', [App\Http\Controllers\DominiosController::class, 'ContenidoGenerado'])->name('dominios.contenido_generado');


    Route::get('dominioeditartipogenerador/{id_dominio_contenido}', [App\Http\Controllers\DominiosController::class, 'EditarTipoGenerador'])->name('dominioeditartipogenerador');
    Route::post('dominioguardarediciontipo/{id_dominio_contenido}', [App\Http\Controllers\DominiosController::class, 'GuardarEditarTipoGenerador'])->name('dominioguardarediciontipo');






Route::post('dominios/{dominio}/contenido/{detalle}/publicar', [App\Http\Controllers\DominiosController::class, 'publicar'])
  ->name('dominios.contenido.publicar');

  Route::post('dominios/{dominio}/contenido/{detalle}/programar', [App\Http\Controllers\DominiosController::class, 'programar'])
    ->name('dominios.contenido.programar');
    //PERFILES
    Route::resource('perfiles','App\Http\Controllers\PerfilesController');
  


});


    Route::view('/politica-de-privacidad', 'legal.politica-privacidad')->name('politica.privacidad');
    Route::resource('auth','App\Http\Controllers\AuthController');
    Route::get('login', [App\Http\Controllers\AuthController::class, 'index'])->name('login');
    Route::post('logout', [App\Http\Controllers\AuthController::class, 'logout'])->name('logout');