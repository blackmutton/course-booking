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
        Schema::create('courses', function (Blueprint $table) {
            $table->id();

            $table->foreignId('teacher_id')
                ->constrained()
                ->restrictOnDelete(); //如果存在相關的 Course 資料，就不能刪除該 Teacher

            $table->string('title');

            $table->text('description')->nullable();

            $table->string('meeting_url')->nullable();

            $table->unsignedInteger('people_limit');

            $table->tinyInteger('status')->default(1);

            $table->dateTime('start_time');

            $table->dateTime('end_time');

            $table->timestamps();

            $table->index('teacher_id');
            $table->index('status');
            $table->index('start_time');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('courses');
    }
};