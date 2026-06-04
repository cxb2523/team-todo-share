<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('user.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});

Broadcast::channel('todo-list.{listId}', function ($user, $listId) {
    $todoList = \App\Models\TodoList::find($listId);
    return $todoList && $user->hasAccessToTodoList($todoList);
});
