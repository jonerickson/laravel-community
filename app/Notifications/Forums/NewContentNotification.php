<?php

declare(strict_types=1);

namespace App\Notifications\Forums;

use App\Mail\Forums\NewContentMail;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notification;

class NewContentNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Model $content,
        public Model $followable
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(User $notifiable): NewContentMail
    {
        return new NewContentMail($this->content, $this->followable, $notifiable)->to($notifiable->email);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $contentType = $this->content instanceof Topic ? 'topic' : 'post';
        $followableType = $this->followable instanceof Topic ? 'topic' : 'forum';

        return [
            'content_type' => $contentType,
            'content_id' => $this->content->id,
            'content_title' => $this->content instanceof Topic ? $this->content->title : $this->content->topic->title,
            'content_author' => $this->content->author->name,
            'followable_type' => $followableType,
            'followable_id' => $this->followable->id,
            'followable_name' => $this->followable instanceof Topic ? $this->followable->title : $this->followable->name,
            'url' => $this->getUrl(),
        ];
    }

    private function getUrl(): string
    {
        if ($this->content instanceof Topic) {
            return route('forums.topics.show', [
                'forum' => $this->content->forum->slug,
                'topic' => $this->content->slug,
            ]);
        }

        return route('forums.topics.show', [
            'forum' => $this->content->topic->forum->slug,
            'topic' => $this->content->topic->slug,
        ]).'#'.$this->content->id;
    }
}
