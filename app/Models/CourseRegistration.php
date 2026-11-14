<?php

namespace App\Models;

use App\Enums\CourseRegistrationStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\URL;

class CourseRegistration extends Model
{
    public const CONFIRMATION_TTL_MINUTES = 30;

    protected $fillable = ['course_id', 'student_id', 'status', 'email_sent_at', 'checked_at', 'cancelled_at', 'confirm_expires_at'];
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_sent_at' => 'datetime',
            'checked_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'confirm_expires_at' => 'datetime',
            'status' => CourseRegistrationStatus::class,
        ];
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function getEnrollmentUrl(): string
    {
        // 設定有效期限的URL
        $expiresAt = $this->confirm_expires_at ?? now()->addMinutes(self::CONFIRMATION_TTL_MINUTES);
        return URL::temporarySignedRoute(
            'api.course-registrations.confirm',
            $expiresAt,
            ['courseRegistration' => $this->getKey()],
        );
    }
}
