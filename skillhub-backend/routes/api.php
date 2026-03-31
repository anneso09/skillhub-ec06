<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\FormationController;
use App\Http\Controllers\ModuleController;
use App\Http\Controllers\EnrollmentController;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login',    [AuthController::class, 'login']);

Route::get('/formations',              [FormationController::class, 'index']);
Route::get('/formations/{id}',         [FormationController::class, 'show']);
Route::get('/formations/{id}/modules', [ModuleController::class, 'index']);

Route::middleware('auth:api')->group(function () {

    Route::post('/logout',  [AuthController::class, 'logout']);
    Route::get('/profile',  [AuthController::class, 'profile']);

    Route::middleware('role:formateur')->group(function () {
        Route::post('/formations',              [FormationController::class, 'store']);
        Route::put('/formations/{id}',          [FormationController::class, 'update']);
        Route::delete('/formations/{id}',       [FormationController::class, 'destroy']);
        Route::get('/formateur/formations',     [FormationController::class, 'mesFormations']);
        Route::post('/formations/{id}/modules', [ModuleController::class, 'store']);
        Route::put('/modules/{id}',             [ModuleController::class, 'update']);
        Route::delete('/modules/{id}',          [ModuleController::class, 'destroy']);
    });

    Route::middleware('role:apprenant')->group(function () {
        Route::post('/formations/{id}/inscription',   [EnrollmentController::class, 'store']);
        Route::delete('/formations/{id}/inscription', [EnrollmentController::class, 'destroy']);
        Route::get('/apprenant/formations',           [EnrollmentController::class, 'mesFormations']);
        Route::put('/formations/{id}/progression',    [EnrollmentController::class, 'updateProgression']);
    });
});