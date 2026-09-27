<?php

use App\Http\Controllers\CuestionarioController;
use App\Http\Controllers\JugadorController;
use App\Http\Controllers\PartidaController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::get('/unirse', [JugadorController::class, 'formularioUnirse'])->name('jugador.unirse');
Route::post('/unirse', [JugadorController::class, 'unirse'])->name('jugador.unirse.guardar');
Route::get('/api/pin/{pin}/equipos', [JugadorController::class, 'equiposPorPin'])->name('jugador.equipos');
Route::get('/jugar/{partida}', [JugadorController::class, 'partida'])->name('jugador.partida');
Route::post('/jugar/{partida}/responder', [JugadorController::class, 'responder'])->name('jugador.responder');

Route::redirect('/login', '/cuestionarios')->name('login');

Route::get('/cuestionarios', [CuestionarioController::class, 'index'])->name('cuestionarios.index');
Route::get('/cuestionarios/crear', [CuestionarioController::class, 'create'])->name('cuestionarios.create');
Route::post('/cuestionarios', [CuestionarioController::class, 'store'])->name('cuestionarios.store');
Route::get('/cuestionarios/{cuestionario}/editar', [CuestionarioController::class, 'edit'])->name('cuestionarios.edit');
Route::put('/cuestionarios/{cuestionario}', [CuestionarioController::class, 'update'])->name('cuestionarios.update');
Route::delete('/cuestionarios/{cuestionario}', [CuestionarioController::class, 'destroy'])->name('cuestionarios.destroy');

Route::post('/cuestionarios/{cuestionario}/partidas', [PartidaController::class, 'store'])->name('partidas.store');
Route::get('/partidas/{partida}', [PartidaController::class, 'show'])->name('partidas.show');
Route::post('/partidas/{partida}/preparar-inicio', [PartidaController::class, 'prepararInicio'])->name('partidas.preparar-inicio');
Route::post('/partidas/{partida}/iniciar-pregunta', [PartidaController::class, 'iniciarPregunta'])->name('partidas.iniciar-pregunta');
Route::post('/partidas/{partida}/cerrar-pregunta', [PartidaController::class, 'cerrarPregunta'])->name('partidas.cerrar-pregunta');
Route::post('/partidas/{partida}/finalizar', [PartidaController::class, 'finalizar'])->name('partidas.finalizar');
