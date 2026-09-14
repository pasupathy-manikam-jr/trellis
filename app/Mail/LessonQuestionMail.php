<?php

namespace App\Mail;

use App\Models\LessonComment;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class LessonQuestionMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public LessonComment $question) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "New question on {$this->question->lesson->title}",
            replyTo: [$this->question->author->email],
        );
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.question');
    }
}
