<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProjetController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AnalyseController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Routes d'authentification
Route::post('/register', [UserController::class, 'register']);
Route::post('/login', [UserController::class, 'login']);

// Routes protégées par authentification
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', [UserController::class, 'profile']);
    Route::post('/logout', [UserController::class, 'logout']);

    Route::get('/projets', [ProjetController::class, 'index']);
    
    // Route pour le classement des plans d'affaires
    Route::get('/projets/classement', [AnalyseController::class, 'classementPlansAffaires']);

    Route::patch('/projets/{id}', [ProjetController::class, 'update']);
});

// Route pour l'enregistrement des projets
Route::post('/projets', [ProjetController::class, 'store']);
