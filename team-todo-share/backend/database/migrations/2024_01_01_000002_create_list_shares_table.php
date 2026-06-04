<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('list_shares', function (Blueprint $table) {
            $table->id();
            $table->foreignId('todo_list_id')->constrained('todo_lists')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->enum('permission', ['view', 'edit', 'admin'])->default('view');
            $table->timestamps();
            
            $table->unique(['todo_list_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('list_shares');
    }
};
