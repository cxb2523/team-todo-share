<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\TodoListController;
use App\Http\Controllers\Api\TaskController;
use App\Http\Controllers\Api\NotificationController;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);

    Route::get('/todo-lists/search-users', [TodoListController::class, 'searchUsers']);
    Route::apiResource('/todo-lists', TodoListController::class);
    Route::post('/todo-lists/{todoList}/share', [TodoListController::class, 'share']);
    Route::delete('/todo-lists/{todoList}/unshare/{user}', [TodoListController::class, 'unshare']);

    Route::apiResource('/todo-lists/{todoList}/tasks', TaskController::class);

    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::get('/notifications/unread', [NotificationController::class, 'unread']);
    Route::put('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
    Route::put('/notifications/mark-all-read', [NotificationController::class, 'markAllAsRead']);
    Route::delete('/notifications/{id}', [NotificationController::class, 'destroy']);
});
