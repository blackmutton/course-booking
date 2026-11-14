<?php

namespace App\Console\Commands;

use App\Enums\CourseRegistrationStatus;
use App\Models\CourseRegistration;
use Illuminate\Console\Command;

class ExpireCourseRegistrations extends Command
{
    protected $signature = 'course-registrations:expire';

    protected $description = '檢查已過期的課程報名並更新狀態為Expired';

    public function handle(): int
    {
        CourseRegistration::query()
            ->where('status', CourseRegistrationStatus::Pending->value)
            ->where(fn($query) => $query->where('confirm_expires_at', '<=', now()))
            ->update(['status' => CourseRegistrationStatus::Expired->value]);

        return self::SUCCESS;
    }
}
