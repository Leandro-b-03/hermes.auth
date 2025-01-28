<?php

use Illuminate\Http\Request;
use App\Http\Middleware\RolePermission;
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
        Route::post('user', [AuthController::class, 'register']);
        Route::post('refresh', [AuthController::class, 'refreshToken']);
        Route::post('user/reset_password', [AuthController::class, 'resetPassword']);
        Route::post('change_password', [AuthController::class, 'changePassword']);
        Route::get('verify_token', [AuthController::class, 'verifyToken'])->middleware('client');
        Route::get('email/verify/{id}/{hash}', [AuthController::class, 'verifyEmail'])->middleware(['auth:api', 'signed'])->name('verification.verify');
    
        Route::group(['middleware' => 'auth:api'], function () {
            Route::post('token', [AuthController::class, 'token']);
            Route::delete('logout', [AuthController::class, 'logout']);
            Route::get('user', [AuthController::class, 'user']);
            Route::middleware(RolePermission::class.':auth.update')->post('user/reset_password_l', [AuthController::class, 'resetPassword']);
            Route::get('user_permissions', [AuthController::class, 'userPermissions']);
        });
    });

    
    Route::get('auth/user/verify_signup_token', [AuthController::class, 'verifySignupToken']);

    Route::group(['prefix'=> 'user', 'middleware' => 'auth:api'], function () {
        Route::get('', [UserController::class, 'index']);
        Route::get('{id}', [UserController::class, 'show']);
        Route::post('invite', [UserController::class, 'invite']);
        Route::put('{id}', [UserController::class, 'update']);
        Route::middleware(RolePermission::class.':auth.delete')->delete('{id}', [UserController::class, 'delete']);
        Route::middleware(RolePermission::class.':auth.update')->post('{id}/activate', [UserController::class, 'reactive']);
    });

    Route::group(['prefix'=> 'roles', 'middleware' => 'auth:api'], function () {
        Route::get('list', [RolesPermissionController::class, 'list']);
        Route::post('create', [RolesPermissionController::class, 'create']);
        Route::middleware(RolePermission::class.':admin')->post('assign', [RolesPermissionController::class, 'assignRole']);
        Route::middleware(RolePermission::class.':admin')->post('revoke', [RolesPermissionController::class, 'revokeRole']);
        Route::middleware(RolePermission::class.':auth.update')->post('assign_permission', [RolesPermissionController::class, 'assignPermissionToUser']);
        Route::middleware(RolePermission::class.':auth.delete')->post('revoke_permission', [RolesPermissionController::class, 'revokePermissionFromUser']);
    });

    Route::group(['prefix'=> 'shipper'], function () {
        Route::group(['middleware' => 'auth:api'], function () {
            Route::get('', [ShipperController::class, 'getUserShipper']);
            Route::put('{id}', [ShipperController::class, 'update']);
        });
        
        Route::get('tax_id/{id}', [ShipperController::class, 'taxId']);
    });
});