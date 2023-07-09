<?php

namespace App\actions;

use App\Exceptions\VanexAPIShipmentException;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Shipment;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;

class sendWhatsAppMessage
{

    public function invoke(Sale $sale)
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer '.'EAASoixA3KuEBACZClvsQZApRTnzNxQvVIZA2Fq4ASwvJRZC1ztXIJnpyBHcWn8KMkDagEAnpYMzekusEnSqkgUQwRx3ZAH65v3cHUh2r6TLhrW8ntZA3flN2XFZCBIWgTP6uSjad3cJ9hMybfqwbgjLZAshGZAHdGGnZC9WeQHvwHvjBnrhDiLbDGgX6BPavHnAQf36HuicuhaaQZDZD',
        ])->post('https://graph.facebook.com/v17.0/103288375943421/messages', [
            'messaging_product' => 'whatsapp',
            "to" => "218931016928",
            'type' => 'template',
            'template' => [
                "name" => "unaswered_notification",
                'language' => [
                    'code' => "ar",
                ],
                "components" => [
                    [
                        "type" => "body",
                        "parameters" => [
                            [
                                "type" => "text",
                                "text" => $sale->Ref
                            ],

                            [
                                "type" => "text",
                                "text" => $sale->client->phone
                            ],

                            [
                                "type" => "text",
                                "text" => $sale->warehouse->name
                            ],

                        ]
                    ]
                ]
            ]
        ]);

        $res_body = $response->body();
        // dd($res_body);

    }
}
