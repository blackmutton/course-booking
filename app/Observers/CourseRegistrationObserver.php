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
        if ($courseRegistration->status === CourseRegistrationStatus::Pending) {
            SendCourseRegistrationEmailJob::dispatch($courseRegistration->student->user, $courseRegistration)->afterCommit();
        }
    }

    /**
     * Handle the CourseRegistration "updated" event.
     */
    public function updated(CourseRegistration $courseRegistration): void
    {
        if ($courseRegistration->wasChanged('status') && $courseRegistration->status === CourseRegistrationStatus::Pending) {
            SendCourseRegistrationEmailJob::dispatch($courseRegistration->student->user, $courseRegistration)->afterCommit();
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
}
