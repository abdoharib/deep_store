<?php

namespace App\Notifications;

use App\Models\Sale;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use NotificationChannels\Fcm\FcmChannel;
use NotificationChannels\Fcm\FcmMessage;

class SaleStatusUpdateNotification extends Notification
{

    /**
     * Create a new notification instance.
     *
     * @return void
     */

     public $sale;
    public function __construct(Sale $sale)
    {
        $this->sale = $sale;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return [FcmChannel::class,'database'];
    }


    public function toFcm($notifiable)
    {
        $mapper = [
            'pending' => 'تعليق',
            'completed' => 'تم أكمال ✅',
            'canceled' => 'تم ألغاء ❌',
            'postponed' => 'تم تاجيل 📅'
        ];

        $sale_ref = '(' .$this->sale->Ref.')';
        $title = $mapper[$this->sale->statut] . ' الطلبية ' . $sale_ref;

        return FcmMessage::create()
        ->setData(['sale_ref' => $this->sale->Ref])
        ->setNotification(\NotificationChannels\Fcm\Resources\Notification::create()
            ->setTitle($title)
            ->setBody( implode(' / ',$this->sale->details->pluck('product')->pluck('name')->toArray()) )
            ->setImage('http://example.com/url-to-image-here.png'));

    }

    public function toArray( $notifiable): array
    {
        return $this->sale->toArray();
    }
}
