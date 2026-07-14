<?php

use App\Http\Controllers\GameController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'game');

Route::prefix('api')->group(function () {
    Route::post('/create', [GameController::class, 'create']);
    Route::post('/join', [GameController::class, 'join']);
    Route::post('/state', [GameController::class, 'state']);
    Route::post('/start', [GameController::class, 'start']);
    Route::post('/answer', [GameController::class, 'answer']);
    Route::post('/validate', [GameController::class, 'validateGuess']);
    Route::post('/replay', [GameController::class, 'replay']);
    Route::post('/daily', [GameController::class, 'dailyAnswer']);
});
