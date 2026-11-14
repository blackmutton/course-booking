<?php

namespace App\Observers;

use App\Enums\CourseRegistrationStatus;
use App\Jobs\SendCourseRegistrationEmailJob;
use App\Models\CourseRegistration;

class CourseRegistrationObserver
{
    /**
     * Handle the CourseRegistration "created" event.
     */
    public function created(CourseRegistration $courseRegistration): void
    {
        $this->sendConfirmationEmail($courseRegistration);
    }

    /**
     * Handle the CourseRegistration "updated" event.
     */
    public function updated(CourseRegistration $courseRegistration): void
    {
        // wasChanged() 方法會檢查指定的屬性是否在更新後有變化
        if (
            $courseRegistration->wasChanged('status') &&
            $courseRegistration->status === CourseRegistrationStatus::Pending
        ) {
            $this->sendConfirmationEmail($courseRegistration);
        }
    }


    /**
     * Handle the CourseRegistration "deleted" event.
     */
    public function deleted(CourseRegistration $courseRegistration): void
    {
        //
    }

    /**
     * Handle the CourseRegistration "restored" event.
     */
    public function restored(CourseRegistration $courseRegistration): void
    {
        //
    }

    /**
     * Handle the CourseRegistration "force deleted" event.
     */
    public function forceDeleted(CourseRegistration $courseRegistration): void
    {
        //
    }

    private function sendConfirmationEmail(CourseRegistration $courseRegistration): void
    {
        if ($courseRegistration->status !== CourseRegistrationStatus::Pending) {
            return;
        }

        $user = $courseRegistration->student?->user;

        if (!$user) {
            return;
        }

        SendCourseRegistrationEmailJob::dispatch($user, $courseRegistration)->afterCommit();
    }
}
