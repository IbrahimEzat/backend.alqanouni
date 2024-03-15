<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_survey_answers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('male_gender')->default(0);
            $table->unsignedBigInteger('female_gender')->default(0);
            $table->unsignedBigInteger('answer_result')->default(0);
            $table->foreignId('survey_answer_id')->constrained('survey_answers')->cascadeOnDelete();
            $table->foreignId('question_id')->constrained('questions')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user_survey_answers');
    }
};
