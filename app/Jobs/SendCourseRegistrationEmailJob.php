<?php

namespace App\Jobs;

use App\Enums\CourseRegistrationStatus;
use App\Mail\CourseRegistrationEmail;
use App\Models\CourseRegistration;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendCourseRegistrationEmailJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected User $user;
    protected CourseRegistration $courseRegistration;

    public $tries = 3;

    public $backoff = [10, 30, 60];

    /**
     * Create a new job instance.
     */
    public function __construct(User $user, CourseRegistration $courseRegistration)
    {
        $this->user = $user;
        $this->courseRegistration = $courseRegistration;
    }

    /**
     * Returns the unique ID for the job.
     * 避免相同 Unique ID 的 Job 重複進入 Queue
     *
     * @return string
     */
    public function uniqueId(): string
    {
        return (string) $this->courseRegistration->id;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $registrationId = $this->courseRegistration->id;
        // 使用 Cache 鎖定，避免同一個報名資料被多個 Job 同時處理
        Cache::lock("course-registration-email:{$registrationId}", 60)
            ->block(5, function () use ($registrationId) {

                $registration = CourseRegistration::find($registrationId);

                if (!$registration) {
                    return;
                }

                // 確認報名狀態與寄送狀態
                if (
                    $registration->status !== CourseRegistrationStatus::Pending ||
                    $registration->email_sent_at !== null
                ) {
                    return;
                }

                Mail::to($this->user->email)->send(new CourseRegistrationEmail($this->user, $registration));

                $registration->email_sent_at = now();
                $registration->save();

                Log::info('Course registration email sent.', [
                    'user_id' => $this->user->id,
                    'email' => $this->user->email,
                    'course_registration_id' => $registrationId,
                ]);
            });
    }

    public function failed(?\Throwable $exception): void
    {
        Log::error('Failed to send course registration email.', [
            'user_id' => $this->user->id,
            'email' => $this->user->email,
            'message' => $exception?->getMessage(),
        ]);
    }
}
