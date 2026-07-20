<?php

namespace App\Jobs;

use App\Mail\VerifyEmail;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendVerifyEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected User $user;

    public $tries = 3;

    public $backoff = [10, 30, 60];


    /**
     * Create a new job instance.
     */
    public function __construct(User $user)
    {
        $this->user = $user;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // 在這裡撰寫發送驗證電子郵件的邏輯
        Mail::to($this->user->email)->send(new VerifyEmail($this->user));
        Log::info(
            'Verification email sent to user.',
            [
                'user_id' => $this->user->id,
                'email' => $this->user->email,
            ]
        );
    }

    public function failed(?\Throwable $exception): void
    {
        Log::error('Failed to send verification email.', [
            'user_id' => $this->user->id,
            'email' => $this->user->email,
            'message' => $exception?->getMessage(),
        ]);
    }
}