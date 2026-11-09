<?php

use App\Http\Controllers\Api\ClassroomController;
use App\Http\Controllers\Api\CourseController;
use App\Http\Controllers\Api\LoginController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/health', function () {
    return response()->json('Minha API está online!');
});

/* Isso
Route::post('/users', [UserController::class, 'store']);
Route::get('/users', [UserController::class, 'index']);
Route::get('/users/{id}', [UserController::class, 'show']);
Route::put('/users/{id}', [UserController::class, 'update']);
Route::delete('/users/{id}', [UserController::class, 'destroy']);
*/

Route::post('/login', [LoginController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    // Equivale a isso
    Route::apiResource('/users', UserController::class);
    Route::apiResource('/classrooms', ClassroomController::class);
    Route::apiResource('/courses', CourseController::class);
    Route::post('/classrooms/{id}/users', [ClassroomController::class, 'manageUser'])
        ->name('classrooms.users');
});
