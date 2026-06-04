<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function ownedLists(): HasMany
    {
        return $this->hasMany(TodoList::class, 'owner_id');
    }

    public function sharedLists(): HasMany
    {
        return $this->hasMany(ListShare::class);
    }

    public function assignedTasks(): HasMany
    {
        return $this->hasMany(Task::class, 'assigned_to');
    }

    public function createdTasks(): HasMany
    {
        return $this->hasMany(Task::class, 'created_by');
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }

    public function hasAccessToTodoList(TodoList $todoList, string $permission = 'view'): bool
    {
        if ($todoList->owner_id === $this->id) {
            return true;
        }

        $share = $todoList->shares()->where('user_id', $this->id)->first();
        
        if (!$share) {
            return $todoList->is_public && $permission === 'view';
        }

        $permissionLevels = ['view' => 1, 'edit' => 2, 'admin' => 3];
        return $permissionLevels[$share->permission] >= $permissionLevels[$permission];
    }
}
