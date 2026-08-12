<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CustomerActivityNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * @param  array{type: string, title: string, message: string, action_url: string, action_label: string, tone: string}  $content
     */
    public function __construct(
        private readonly array $content,
    ) {
        $this->afterCommit();
    }

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->content['title'])
            ->greeting("Olá, {$notifiable->name}!")
            ->line($this->content['message'])
            ->action(
                $this->content['action_label'],
                url($this->content['action_url']),
            )
            ->line('Você também pode acompanhar tudo pela sua conta.');
    }

    /**
     * @return array<string, string>
     */
    public function toArray(object $notifiable): array
    {
        return $this->content;
    }
}
