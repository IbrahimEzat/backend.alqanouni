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
        Schema::create('opinion_discussion_points', function (Blueprint $table) {
            $table->id();
            $table->foreignId('opinion_discussion_id')->constrained('opinion_discussions')->cascadeOnDelete();
            $table->BigInteger('count_points')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('opinion_discussion_points');
    }
};
