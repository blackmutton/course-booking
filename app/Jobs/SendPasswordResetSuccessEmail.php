<?php

namespace App\Jobs;

use App\Mail\PasswordResetSuccessEmail;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendPasswordResetSuccessEmail implements ShouldQueue
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
        // 在這裡撰寫密碼重設通知郵件的邏輯
        Log::info(
            'Password reset email sent to user.',
            [
                'user_id' => $this->user->id,
                'email' => $this->user->email,
            ]
        );

        Mail::to($this->user->email)->send(new PasswordResetSuccessEmail($this->user));
    }
}
