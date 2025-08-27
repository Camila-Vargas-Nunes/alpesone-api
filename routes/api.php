<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\IntegratorDataController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Integrator Data API Routes
Route::middleware(['api.auth'])->group(function () {
    Route::get('/integrator/latest', [IntegratorDataController::class, 'latest']);
    Route::apiResource('/integrator', IntegratorDataController::class);
});
