<?php

namespace App\Console\Commands;

use App\Enums\CourseRegistrationStatus;
use App\Models\CourseRegistration;
use Illuminate\Console\Command;

class ExpireCourseRegistrations extends Command
{
    protected $signature = 'course-registrations:expire
                            {--all : 檢查所有 Pending 報名}
                            {--student_id= : 指定學生 ID}
                            {--course_id= : 指定課程 ID}';

    protected $description = '檢查已過期的課程報名並更新狀態為Expired';

    public function handle(): int
    {
        $studentId = $this->option('student_id');
        $courseId = $this->option('course_id');
        $query = CourseRegistration::query()
            ->where('status', CourseRegistrationStatus::Pending->value)
            ->where(fn($query) => $query->whereNotNull('confirm_expires_at')->where('confirm_expires_at', '<=', now()));

        if ($studentId) {
            $query->where('student_id', $studentId);
        }
        if ($courseId) {
            $query->where('course_id', $courseId);
        }
        $query->update(['status' => CourseRegistrationStatus::Expired->value]);

        return self::SUCCESS;
    }
}
