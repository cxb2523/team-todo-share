<?php

namespace App\Http\Controllers\Api;

use App\Events\TaskStatusUpdated;
use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Models\TodoList;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    public function index(Request $request, TodoList $todoList)
    {
        if (!$request->user()->hasAccessToTodoList($todoList)) {
            return response()->json(['message' => '无权访问此清单。'], 403);
        }

        $tasks = $todoList->tasks()->with(['assignedUser', 'createdBy'])->get();

        return response()->json($tasks);
    }

    public function store(Request $request, TodoList $todoList)
    {
        if (!$request->user()->hasAccessToTodoList($todoList, 'edit')) {
            return response()->json(['message' => '无权在此清单创建任务。'], 403);
        }

        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'in:pending,in_progress,completed,cancelled',
            'assigned_to' => 'nullable|exists:users,id',
            'due_date' => 'nullable|date',
            'priority' => 'integer|min:1|max:5',
        ]);

        $task = $todoList->tasks()->create([
            'title' => $request->title,
            'description' => $request->description,
            'status' => $request->status ?? 'pending',
            'assigned_to' => $request->assigned_to,
            'created_by' => $request->user()->id,
            'due_date' => $request->due_date,
            'priority' => $request->priority ?? 1,
        ]);

        $task->load(['assignedUser', 'createdBy']);

        if ($request->assigned_to && $request->assigned_to !== $request->user()->id) {
            $this->createNotification($request->assigned_to, 'task_assigned', "{$request->user()->name} 为您分配了任务 \"{$task->title}\"", [
                'task_id' => $task->id,
                'task_title' => $task->title,
                'list_id' => $todoList->id,
                'assigned_by' => $request->user()->name,
            ]);
        }

        return response()->json($task, 201);
    }

    public function show(Request $request, TodoList $todoList, Task $task)
    {
        if (!$request->user()->hasAccessToTodoList($todoList)) {
            return response()->json(['message' => '无权访问此任务。'], 403);
        }

        return response()->json($task->load(['assignedUser', 'createdBy']));
    }

    public function update(Request $request, TodoList $todoList, Task $task)
    {
        if (!$request->user()->hasAccessToTodoList($todoList, 'edit')) {
            return response()->json(['message' => '无权编辑此任务。'], 403);
        }

        $request->validate([
            'title' => 'string|max:255',
            'description' => 'nullable|string',
            'status' => 'in:pending,in_progress,completed,cancelled',
            'assigned_to' => 'nullable|exists:users,id',
            'due_date' => 'nullable|date',
            'priority' => 'integer|min:1|max:5',
        ]);

        $oldStatus = $task->status;
        $oldAssignedTo = $task->assigned_to;

        $task->update($request->only(['title', 'description', 'status', 'assigned_to', 'due_date', 'priority']));
        $task->load(['assignedUser', 'createdBy']);

        if ($request->has('status') && $oldStatus !== $request->status) {
            event(new TaskStatusUpdated($task, $request->user()));

            if ($task->assigned_to && $task->assigned_to !== $request->user()->id) {
                $this->createNotification($task->assigned_to, 'task_status_updated', "任务 \"{$task->title}\" 的状态已更新为 {$request->status}", [
                    'task_id' => $task->id,
                    'task_title' => $task->title,
                    'status' => $request->status,
                    'updated_by' => $request->user()->name,
                ]);
            }
        }

        if ($request->has('assigned_to') && $oldAssignedTo !== $request->assigned_to && $request->assigned_to !== $request->user()->id) {
            $this->createNotification($request->assigned_to, 'task_assigned', "{$request->user()->name} 为您分配了任务 \"{$task->title}\"", [
                'task_id' => $task->id,
                'task_title' => $task->title,
                'list_id' => $todoList->id,
                'assigned_by' => $request->user()->name,
            ]);
        }

        return response()->json($task);
    }

    public function destroy(Request $request, TodoList $todoList, Task $task)
    {
        if (!$request->user()->hasAccessToTodoList($todoList, 'edit')) {
            return response()->json(['message' => '无权删除此任务。'], 403);
        }

        $task->delete();

        return response()->json(['message' => '任务已成功删除。']);
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
