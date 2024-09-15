<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ActorController;
use App\Http\Controllers\ActorsCategoryController;
use App\Http\Controllers\Auth\AuthController;

Route::get('/user', function (Request $request) {



    return $request->user();
})->middleware('auth:sanctum');


Route::middleware('auth:sanctum')->group(function () {

    Route::post('login', [AuthController::class, 'login']);


    Route::get('/', [ActorController::class, 'index']);

    Route::get('actors/categories', [ActorController::class, 'indexByCategories']);

    Route::get('actors/{id}', [ActorController::class, 'show']);
    Route::post('actors/', [ActorController::class, 'create']);
    Route::post('actors/{id}', [ActorController::class, 'update']);
    Route::delete('actors/{id}', [ActorController::class, 'destroy']);
    Route::delete('actors/{actor_id}/attachments', [ActorController::class, 'deleteAttachments']);



    Route::get('/categories', [ActorsCategoryController::class, 'index']);
    Route::get('/categories/{id}', [ActorsCategoryController::class, 'show']);
    Route::post('/categories', [ActorsCategoryController::class, 'create']);
    Route::post('/categories/{id}', [ActorsCategoryController::class, 'update']);
    Route::delete('/categories/{id}', [ActorsCategoryController::class, 'destroy']);
});
