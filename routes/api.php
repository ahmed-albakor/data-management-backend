<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ActorController;
use App\Http\Controllers\ActorsCategoryController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Middleware\AdminMiddleware;

Route::get('/user', function (Request $request) {
   $request->headers->set('Accept', 'application/json');
    return $request->user() !== null ? "success" : "faluire";
})->middleware('auth:sanctum');

Route::post('login', [AuthController::class, 'login']);

// حماية الروابط بواسطة `auth:sanctum` و `AdminMiddleware`
Route::middleware([AdminMiddleware::class,'auth:sanctum'])->group(function () {

    // طرق خاصة بالمصادقة
    Route::post('changePassword', [AuthController::class, 'changePassword']);
    Route::post('logout', [AuthController::class, 'logout']);

    // طرق خاصة بالممثلين
    Route::get('actors/', [ActorController::class, 'index']);
    Route::get('actors/categories', [ActorController::class, 'indexByCategories']);
    Route::get('actors/{id}', [ActorController::class, 'show']);
    Route::post('actors/', [ActorController::class, 'create']);
    Route::post('actors/{id}', [ActorController::class, 'update']);
    Route::delete('actors/{id}', [ActorController::class, 'destroy']);
    Route::delete('actors/{actor_id}/attachments', [ActorController::class, 'deleteAttachments']);

    // طرق خاصة بالفئات
    Route::get('/categories', [ActorsCategoryController::class, 'index']);
    Route::get('/categories/{id}', [ActorsCategoryController::class, 'show']);
    Route::post('/categories', [ActorsCategoryController::class, 'create']);
    Route::post('/categories/{id}', [ActorsCategoryController::class, 'update']);
    Route::delete('/categories/{id}', [ActorsCategoryController::class, 'destroy']);
});
