<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class StoreNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public $data;
    /**
     * Create a new notification instance.
     */
    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the mail representation of the notification.
     */
//    public function toMail(object $notifiable): MailMessage
//    {
//        return (new MailMessage)
//                    ->line('The introduction to the notification.')
//                    ->action('Notification Action', url('/'))
//                    ->line('Thank you for using our application!');
//    }


    public function toDatabase($notifiable)
    {
        return $this->data;
    }

    /**
     * Get the broadcastable representation of the notification.
     */
    // public function toBroadcast(object $notifiable): BroadcastMessage
    // {
    //     return new BroadcastMessage([
    //         'data' => [
    //             'blog_id' => $this->blog->id,
    //             'blog_title' => $this->blog->title,
    //             'blog_slug' => $this->blog->slug,
    //             'blog_user' => $this->blog->user->name,
    //             'created_date' => $this->blog->created_at->format('M d, Y'),
    //         ]
    //     ]);
    // }
}
