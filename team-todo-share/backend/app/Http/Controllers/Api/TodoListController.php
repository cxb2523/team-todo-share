<?php

namespace App\Http\Controllers\Api;

use App\Events\ListShared;
use App\Http\Controllers\Controller;
use App\Models\ListShare;
use App\Models\TodoList;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TodoListController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        
        $ownedLists = $user->ownedLists()->with(['owner', 'shares.user'])->get();
        
        $sharedLists = $user->sharedLists()->with(['todoList.owner', 'todoList.shares.user'])
            ->get()
            ->pluck('todoList');

        $allLists = $ownedLists->merge($sharedLists)->unique('id');

        return response()->json($allLists->values());
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_public' => 'boolean',
        ]);

        $todoList = $request->user()->ownedLists()->create([
            'title' => $request->title,
            'description' => $request->description,
            'is_public' => $request->is_public ?? false,
        ]);

        return response()->json($todoList->load('owner', 'shares.user'), 201);
    }

    public function show(Request $request, TodoList $todoList)
    {
        if (!$request->user()->hasAccessToTodoList($todoList)) {
            return response()->json(['message' => '无权访问此清单。'], 403);
        }

        return response()->json($todoList->load(['owner', 'shares.user', 'tasks.assignedUser', 'tasks.createdBy']));
    }

    public function update(Request $request, TodoList $todoList)
    {
        if (!$request->user()->hasAccessToTodoList($todoList, 'edit')) {
            return response()->json(['message' => '无权编辑此清单。'], 403);
        }

        $request->validate([
            'title' => 'string|max:255',
            'description' => 'nullable|string',
            'is_public' => 'boolean',
        ]);

        $todoList->update($request->only(['title', 'description', 'is_public']));

        return response()->json($todoList->load('owner', 'shares.user'));
    }

    public function destroy(Request $request, TodoList $todoList)
    {
        if (!$request->user()->hasAccessToTodoList($todoList, 'admin')) {
            return response()->json(['message' => '无权删除此清单。'], 403);
        }

        $todoList->delete();

        return response()->json(['message' => '清单已成功删除。']);
    }

    public function share(Request $request, TodoList $todoList)
    {
        if (!$request->user()->hasAccessToTodoList($todoList, 'admin')) {
            return response()->json(['message' => '无权共享此清单。'], 403);
        }

        $request->validate([
            'email' => 'required|email|exists:users,email',
            'permission' => 'required|in:view,edit,admin',
        ]);

        $userToShare = User::where('email', $request->email)->first();

        if ($userToShare->id === $todoList->owner_id) {
            return response()->json(['message' => '不能与清单所有者共享。'], 400);
        }

        $existingShare = ListShare::where('todo_list_id', $todoList->id)
            ->where('user_id', $userToShare->id)
            ->first();

        if ($existingShare) {
            $existingShare->update(['permission' => $request->permission]);
            $share = $existingShare;
        } else {
            $share = ListShare::create([
                'todo_list_id' => $todoList->id,
                'user_id' => $userToShare->id,
                'permission' => $request->permission,
            ]);
        }

        $share->load('user', 'todoList');
        
        event(new ListShared($share, $request->user()));

        $this->createNotification($userToShare->id, 'list_shared', "{$request->user()->name} 与您共享了清单 \"{$todoList->title}\"", [
            'list_id' => $todoList->id,
            'list_title' => $todoList->title,
            'shared_by' => $request->user()->name,
        ]);

        return response()->json($share);
    }

    public function unshare(Request $request, TodoList $todoList, User $user)
    {
        if (!$request->user()->hasAccessToTodoList($todoList, 'admin')) {
            return response()->json(['message' => '无权取消共享此清单。'], 403);
        }

        $share = ListShare::where('todo_list_id', $todoList->id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $share->delete();

        return response()->json(['message' => '已成功取消共享。']);
    }

    public function searchUsers(Request $request)
    {
        $request->validate([
            'query' => 'required|string|min:2',
        ]);

        $users = User::where('name', 'like', "%{$request->query}%")
            ->orWhere('email', 'like', "%{$request->query}%")
            ->where('id', '!=', $request->user()->id)
            ->limit(10)
            ->get(['id', 'name', 'email']);

        return response()->json($users);
    }

    protected function createNotification($userId, $type, $message, $data = [])
    {
        \App\Models\Notification::create([
            'user_id' => $userId,
            'type' => $type,
            'message' => $message,
            'data' => $data,
            'read' => false,
        ]);
    }
}
