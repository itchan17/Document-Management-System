<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\HtmlString;


class NewAccount extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(protected string $password, protected ?Model $tenant = null)
    {
        $this->afterCommit();
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {

        return (new MailMessage)
            ->subject("Your Account has been created on Office Of The City Engineer's Document Management System")
            ->line("Here are your login details:")
            ->line(new HtmlString("<strong>Email</strong> : {$notifiable->email}"))
            ->line(new HtmlString("<strong>Temporary password</strong> : {$this->password}"))
            ->line("You will be prompted to change this temporary password at your next login.")
            ->action('Go to app', filament()->getUrl($this->tenant));
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
