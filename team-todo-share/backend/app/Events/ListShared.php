<?php

namespace App\Events;

use App\Models\ListShare;
use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ListShared implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $listShare;
    public $sharedBy;

    public function __construct(ListShare $listShare, User $sharedBy)
    {
        $this->listShare = $listShare;
        $this->sharedBy = $sharedBy;
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('user.' . $this->listShare->user_id),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'list_id' => $this->listShare->todo_list_id,
            'list_title' => $this->listShare->todoList->title,
            'shared_by' => $this->sharedBy->name,
            'permission' => $this->listShare->permission,
            'message' => "{$this->sharedBy->name} 与您共享了清单 \"{$this->listShare->todoList->title}\"",
        ];
    }

    public function broadcastAs(): string
    {
        return 'list.shared';
    }
}
