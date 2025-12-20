<?php

declare(strict_types=1);

namespace App\Mail\Forums;

use App\Models\Topic;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NewContentMail extends Mailable implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public Model $content,
        public Model $followable,
        public User $recipient
    ) {
        if ($this->content instanceof Topic) {
            $this->content->loadMissing('posts');
        }
    }

    public function envelope(): Envelope
    {
        $followableName = $this->followable instanceof Topic
            ? $this->followable->title
            : $this->followable->name;

        $contentType = $this->content instanceof Topic ? 'New Topic' : 'New Reply';

        return new Envelope(
            subject: sprintf('%s in %s', $contentType, $followableName),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.forums.new-content',
        );
    }

    /**
     * @return array{}
     */
    public function attachments(): array
    {
        return [];
    }
}
