<?php

namespace App\Listeners;

use App\Jobs\SendPasswordResetSuccessEmail;
use App\Mail\PasswordResetSuccessEmail;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Mail;

class SendPasswordResetNotification
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(PasswordReset $event): void
    {
        $user = $event->user;

        SendPasswordResetSuccessEmail::dispatch($user)->onQueue('emails');
    }
}
