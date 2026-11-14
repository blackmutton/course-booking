<?php

namespace App\Jobs;

use App\Enums\CourseRegistrationStatus;
use App\Mail\CourseRegistrationEmail;
use App\Models\CourseRegistration;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendCourseRegistrationEmailJob implements ShouldQueue
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
     * Execute the job.
     */
    public function handle(): void
    {
        // 重新確認寄送狀態
        $registration = CourseRegistration::findOrFail(
            $this->courseRegistration->id
        );
        // 避免重複寄送
        if ($registration->status !== CourseRegistrationStatus::Pending || $registration->email_sent_at) {
            return;
        }

        // 在這裡撰寫發送課程報名電子郵件的邏輯
        Mail::to($this->user->email)->send(new CourseRegistrationEmail($this->user, $this->courseRegistration));
        $registration->whereNull('email_sent_at')->update(['email_sent_at' => now()]);
        Log::info(
            'Course registration email sent to user.',
            [
                'user_id' => $this->user->id,
                'email' => $this->user->email,
            ]
        );
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
