<?php

namespace App\Events;

use App\Models\Task;
use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TaskStatusUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $task;
    public $user;

    public function __construct(Task $task, User $user)
    {
        $this->task = $task;
        $this->user = $user;
    }

    public function broadcastOn(): array
    {
        $channels = [];
        
        if ($this->task->assigned_to) {
            $channels[] = new PrivateChannel('user.' . $this->task->assigned_to);
        }
        
        $channels[] = new PrivateChannel('todo-list.' . $this->task->todo_list_id);
        
        return $channels;
    }

    public function broadcastWith(): array
    {
        return [
            'task_id' => $this->task->id,
            'task_title' => $this->task->title,
            'status' => $this->task->status,
            'updated_by' => $this->user->name,
            'todo_list_id' => $this->task->todo_list_id,
            'message' => "任务 \"{$this->task->title}\" 状态已更新为 {$this->task->status}",
        ];
    }

    public function broadcastAs(): string
    {
        return 'task.status.updated';
    }
}
