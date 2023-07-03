<?php

namespace App\Notifications;

use App\Models\Sale;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use NotificationChannels\Fcm\FcmChannel;
use NotificationChannels\Fcm\FcmMessage;
use NotificationChannels\Fcm\Resources\WebpushConfig;
use NotificationChannels\Fcm\Resources\WebpushFcmOptions;

class NewSaleNotification extends Notification
{

    /**
     * Create a new notification instance.
     *
     * @return void
     */

     public $sale;
    public function __construct(Sale $sale)
    {
        $this->sale= $sale;
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
        $link = '';
        if($notifiable->hasRole('Delivery')){
            $link = "https://delivery.deepstore.ly/app-transaction-detail.html?sale=".$this->sale->id;
        }else{
            $link = "https://app.deepstore.ly/app/sales/detail/".$this->sale->id;
        }

        $title = '📝 طلبية جديدة '. '(' .$this->sale->Ref.')';
        return FcmMessage::create()
        ->setData(['sale_ref' => $this->sale->Ref])
        ->setNotification(\NotificationChannels\Fcm\Resources\Notification::create()
            ->setTitle($title)
            ->setBody( implode(' / ',$this->sale->details->pluck('product')->pluck('name')->toArray()) )
            ->setImage('http://example.com/url-to-image-here.png'))
            ->setWebPush(WebpushConfig::create()->setFcmOptions(
                WebpushFcmOptions::create()->setLink($link)
            ));

    }

    public function toArray( $notifiable): array
    {
        return $this->sale->toArray();
    }
}
