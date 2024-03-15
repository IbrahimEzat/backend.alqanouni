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
        Schema::create('subsribe_service_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscribe_id')->constrained('service_subscriptions')->cascadeOnDelete();
            $table->text('message');
            $table->string('attachment')->nullable();
            $table->foreignId('sender_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subsribe_service_messages');
    }
};
