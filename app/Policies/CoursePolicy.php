<?php

namespace App\Policies;

use App\Models\Course;
use App\Models\Teacher;
use App\Models\User;

class CoursePolicy
{
    /**
     * Perform pre-authorization checks.
     */
    public function before(User $user, string $ability): bool|null
    {
        if ($user->isAdmin() && $ability !== 'delete') {
            return true;
        }


        return null;
    }


    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user, Course $course): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Course $course): bool
    {
        return true;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user, Teacher $teacher): bool
    {
        return $teacher->user_id === $user->id;
    }


    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Course $course): bool
    {
        return $course->teacher->user_id === $user->id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Course $course): bool
    {
        if ($course->status) {
            return false;
        }

        if ($course->start_time->isPast()) {
            return false;
        }

        if ($course->registrations()->exists()) {
            return false;
        }

        if ($course->waitingLists()->exists()) {
            return false;
        }

        return $course->teacher->user_id === $user->id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Course $course): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Course $course): bool
    {
        return false;
    }
}