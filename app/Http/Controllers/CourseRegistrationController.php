<?php

namespace App\Http\Controllers;

use App\Enums\CourseRegistrationStatus;
use App\Models\CourseRegistration;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CourseRegistrationController extends Controller
{
    public function confirm(CourseRegistration $courseRegistration): JsonResponse
    {
        $courseRegistration = DB::transaction(function () use ($courseRegistration) {
            $registration = CourseRegistration::query()
                ->lockForUpdate()
                ->findOrFail($courseRegistration->getKey());

            if ($registration->status === CourseRegistrationStatus::Cancelled) {
                throw ValidationException::withMessages([
                    'registration' => ['Cancelled registrations cannot be confirmed.'],
                ]);
            }

            if ($registration->status === CourseRegistrationStatus::Expired ||
                ($registration->status === CourseRegistrationStatus::Pending && (!$registration->confirm_expires_at || $registration->confirm_expires_at->isPast()))) {
                if ($registration->status === CourseRegistrationStatus::Pending) {
                    $registration->update(['status' => CourseRegistrationStatus::Expired]);
                }
                throw ValidationException::withMessages([
                    'registration' => ['Confirmation link has expired. Please register again.'],
                ]);
            }

            if ($registration->status !== CourseRegistrationStatus::Pending && $registration->status !== CourseRegistrationStatus::Confirmed) {
                throw ValidationException::withMessages([
                    'registration' => ['This registration cannot be confirmed.'],
                ]);
            }

            if ($registration->status !== CourseRegistrationStatus::Confirmed) {
                $registration->update(['status' => CourseRegistrationStatus::Confirmed]);
            }

            return $registration;
        });

        return response()->json([
            'message' => 'Success',
            'registration' => $courseRegistration,
        ]);
    }
}
