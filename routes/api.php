<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\RolesPermissionController;

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
Route::group(['prefix' => 'v1'], function () {
    Route::group(['prefix'=> 'auth'], function () {
        Route::post('login', [AuthController::class, 'login']);
        Route::post('register', [AuthController::class, 'register']);
        Route::post('refresh', [AuthController::class, 'refreshToken']);
    
        Route::group(['middleware' => 'auth:api'], function () {
            Route::post('token', [AuthController::class, 'token']);
            Route::delete('logout', [AuthController::class, 'logout']);
            Route::get('user', [AuthController::class, 'user']);
            Route::get('user_permissions', [AuthController::class, 'user_permissions']);	
        });
    });

    Route::group(['prefix'=> 'roles', 'middleware' => 'auth:api'], function () {
        Route::get('list', [RolesPermissionController::class, 'list']);
        Route::post('create', [RolesPermissionController::class, 'create']);
        Route::post('assign_role', [RolesPermissionController::class, 'assignRole']);
    });
});