<?php

namespace App\Mail;

use App\Models\LessonComment;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class LessonReplyMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public LessonComment $reply) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: "{$this->reply->author->name} answered your question");
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.reply');
    }
}
