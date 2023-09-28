<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\TaskController;
use App\Models\User;

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

Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
    return $request->user();
});



Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/projects', [ProjectController::class, 'index']);
    Route::post('/projects', [ProjectController::class, 'store']);
    Route::patch('/projects', [ProjectController::class, 'update']);
    Route::get('/projects/{id}/users/{username}', [ProjectController::class, 'findProposedUsers']);
    Route::post('/projects/add-user', [ProjectController::class, 'addUser']);
    Route::get('/projects/{id}/users', [ProjectController::class, 'getUsersInProject']);
    Route::post('/projects/change-status', [ProjectController::class, 'updateProjectStatus']);
    Route::post('/projects/leave', [ProjectController::class, 'leaveProject']);
    Route::get('/projects/{id}/permissions', [ProjectController::class, 'getPermissionsInProject']);
    Route::post('/projects/add-permission', [ProjectController::class, 'addPermission']);
    Route::patch('/projects/remove-permission', [ProjectController::class, 'removePermission']);
    Route::patch('/projects/update-permission-settings', [ProjectController::class, 'updatePermissionSettings']);
    Route::patch('/projects/update-user-permission', [ProjectController::class, 'updateUserPermissions']);
    Route::post('/projects/delete-user', [ProjectController::class, 'deleteUserFromProject']);
    Route::get('/tasks/{id}', [TaskController::class, 'index']);
    Route::post('/tasks/create', [TaskController::class, 'store']);
    Route::patch('/tasks/update', [TaskController::class, 'update']);
    Route::delete('/tasks/{id}', [TaskController::class, 'destroy']);
});
