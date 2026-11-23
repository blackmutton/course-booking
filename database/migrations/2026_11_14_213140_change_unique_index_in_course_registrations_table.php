<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 新增虛擬欄位 active_slot，並建立唯一索引，避免同一學生在同一課程有多筆 Pending / Confirmed 的報名紀錄
        // 這樣的設計得以保存所有預約歷史紀錄並且允許取消後再次預約的操作
        DB::statement("
            ALTER TABLE course_registrations
            ADD COLUMN active_slot TINYINT
            GENERATED ALWAYS AS (
                CASE
                    WHEN status IN (0, 1) THEN 1
                    ELSE NULL
                END
            ) STORED
        ");

        DB::statement("
            CREATE UNIQUE INDEX course_registrations_active_unique
            ON course_registrations (
                course_id,
                student_id,
                active_slot
            )
        ");

        Schema::table('course_registrations', function (Blueprint $table) {
            $table->dropUnique(['course_id', 'student_id']);
        });
    }

    public function down(): void
    {
        DB::statement("
            DROP INDEX course_registrations_active_unique
            ON course_registrations
        ");

        DB::statement("
            ALTER TABLE course_registrations
            DROP COLUMN active_slot
        ");

        Schema::table('course_registrations', function (Blueprint $table) {
            $table->unique(['course_id', 'student_id']);
        });
    }
};
