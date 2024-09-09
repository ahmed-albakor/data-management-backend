<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ActorController;

Route::get('/user', function (Request $request) {



    return $request->user();
})->middleware('auth:sanctum');


Route::prefix('actors')->group(function () {

    Route::get('/', [ActorController::class, 'index']);

    Route::get('/{id}', [ActorController::class, 'show']);

    Route::post('/', [ActorController::class, 'create']);

    Route::post('/{id}', [ActorController::class, 'update']);

    Route::delete('/{id}', [ActorController::class, 'destroy']);

    Route::delete('/{actor_id}/attachments', [ActorController::class, 'deleteAttachments']);
});
