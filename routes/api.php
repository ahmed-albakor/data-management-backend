<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ActorController;
use App\Http\Controllers\ActorsCategoryController;

Route::get('/user', function (Request $request) {



    return $request->user();
})->middleware('auth:sanctum');


Route::prefix('actors')->group(function () {

    Route::get('/', [ActorController::class, 'index']);
    
    Route::get('/categories', [ActorController::class, 'indexByCategories']);

    Route::get('/{id}', [ActorController::class, 'show']);

    Route::post('/', [ActorController::class, 'create']);

    Route::post('/{id}', [ActorController::class, 'update']);

    Route::delete('/{id}', [ActorController::class, 'destroy']);

    Route::delete('/{actor_id}/attachments', [ActorController::class, 'deleteAttachments']);
});


Route::get('/categories', [ActorsCategoryController::class, 'index']);
Route::get('/categories/{id}', [ActorsCategoryController::class, 'show']);
Route::post('/categories', [ActorsCategoryController::class, 'create']);
Route::post('/categories/{id}', [ActorsCategoryController::class, 'update']);
Route::delete('/categories/{id}', [ActorsCategoryController::class, 'destroy']);