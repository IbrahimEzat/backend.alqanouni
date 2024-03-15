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
        Schema::create('comment_opinion_points', function (Blueprint $table) {
            $table->id();
            $table->foreignId('comment_opinion_discussion_id')->constrained('comment_opinion_discussions')->cascadeOnDelete();
            $table->BigInteger('count_points')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('comment_opinion_points');
    }
};
