<?php

namespace App\Mail;

use App\Models\CourseRegistration;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

// Mailable 不實作 ShouldQueue：寄信在 Job 內同步執行，
// 失敗時例外會讓 Job 依 $tries / $backoff 重試
class CourseRegistrationEmail extends Mailable
{
    use Queueable, SerializesModels;

    protected User $user;
    protected CourseRegistration $courseRegistration;

    /**
     * Create a new message instance.
     */
    public function __construct(User $user, CourseRegistration $courseRegistration)
    {
        $this->user = $user;
        $this->courseRegistration = $courseRegistration;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Course Registration',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'mails.courseRegistration',
            with: [
                'user' => $this->user,
                'course' => $this->courseRegistration->course,
                'url' => $this->courseRegistration->getEnrollmentUrl(),
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
