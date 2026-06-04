<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Task extends Model
{
    use HasFactory;

    protected $fillable = [
        'todo_list_id',
        'title',
        'description',
        'status',
        'assigned_to',
        'created_by',
        'due_date',
        'priority',
    ];

    protected $casts = [
        'due_date' => 'datetime',
        'priority' => 'integer',
        'todo_list_id' => 'integer',
        'assigned_to' => 'integer',
        'created_by' => 'integer',
    ];

    public function todoList(): BelongsTo
    {
        return $this->belongsTo(TodoList::class);
    }

    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
