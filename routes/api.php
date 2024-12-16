<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\UserController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\ShipperController;
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
    Route::post('oauth', [AuthController::class, 'oauth_client']);
    Route::group(['prefix'=> 'auth'], function () {
        Route::post('login', [AuthController::class, 'login']);
        Route::post('register', [AuthController::class, 'register']);
        Route::post('refresh', [AuthController::class, 'refreshToken']);
        Route::post('reset_password', [AuthController::class, 'resetPassword']);
        Route::post('change_password', [AuthController::class, 'changePassword']);
        Route::get('verify_token', [AuthController::class, 'verifyToken'])->middleware('client');
        Route::get('email/verify/{id}/{hash}', [AuthController::class, 'verifyEmail'])->middleware(['auth:api', 'signed'])->name('verification.verify');
    
        Route::group(['middleware' => 'auth:api'], function () {
            Route::post('token', [AuthController::class, 'token']);
            Route::delete('logout', [AuthController::class, 'logout']);
            Route::get('user', [AuthController::class, 'user']);
            Route::get('user_permissions', [AuthController::class, 'userPermissions']);
        });
    });

    
    Route::get('auth/user/verify_signup_token', [AuthController::class, 'verifySignupToken']);

    Route::group(['prefix'=> 'users', 'middleware' => 'auth:api'], function () {
        Route::get('', [UserController::class, 'index']);
        Route::get('{id}', [UserController::class, 'show']);
        Route::post('invite', [UserController::class, 'invite']);
        Route::put('{id}', [UserController::class, 'update']);
        Route::post('{id}', [UserController::class, 'disable']);
    });

    Route::group(['prefix'=> 'roles', 'middleware' => 'auth:api'], function () {
        Route::get('list', [RolesPermissionController::class, 'list']);
        Route::post('create', [RolesPermissionController::class, 'create']);
        Route::post('assign_role', [RolesPermissionController::class, 'assignRole']);
    });

    Route::group(['prefix'=> 'shipper'], function () {
        Route::group(['middleware' => 'auth:api'], function () {
            Route::get('', [ShipperController::class, 'getUserShipper']);
            Route::put('{id}', [ShipperController::class, 'update']);
        });
        
        Route::get('tax_id/{id}', [ShipperController::class, 'taxId']);
    });
});