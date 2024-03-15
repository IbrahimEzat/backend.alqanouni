<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('chats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id1')->constrained('users')->cascadeOnDelete();
            $table->foreignId('user_id2')->constrained('users')->cascadeOnDelete();
            // $table->foreignId('sender')->constrained('users')->cascadeOnDelete();
            // $table->foreignId('receiver')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });
    }
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chats');
    }
};
