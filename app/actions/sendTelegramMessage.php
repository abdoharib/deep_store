<?php

namespace App\actions;

use App\Models\Ad;
use Carbon\Carbon;
use Illuminate\Support\Carbon as SupportCarbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Symfony\Component\ErrorHandler\Debug;

class sendTelegramMessage
{
    public function invoke(string $message =''){
        dd($message);

        try {
            $response = Http::post('https://api.telegram.org/bot6107962869:AAEnLYUlxM5Xqn4LqZ14nXzodkx7oZf8Q6A/sendMessage',[
                'chat_id' => '-968418833',
                'text' => $message,
            ]);
            $response->throw();
        } catch (\Exception $e) {
            Log::debug($e->getMessage());
        }
    }
}
