<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Telegram\Bot\Laravel\Facades\Telegram;

class TelegramController extends Controller
{
    public function webhook(Request $request)
    {

        $chat = $request['message']['chat']['id']
            ?? $request['callback_query']['message']['chat']['id']
            ?? null;

        $text = strtolower(trim($request['message']['text'] ?? ''));
        $callback = $request['callback_query']['data'] ?? null;

        if (!$chat) return response()->json(['ok'=>true]);

        $menu = [

            'burger'=>[
                'name'=>'Burger',
                'price'=>5,
                'img'=>'https://images.unsplash.com/photo-1550547660-d9450f859349'
            ],

            'pizza'=>[
                'name'=>'Pizza',
                'price'=>7,
                'img'=>'https://images.unsplash.com/photo-1601924582975-7e7e6c6b8c64'
            ],

            'coffee'=>[
                'name'=>'Coffee',
                'price'=>3,
                'img'=>'https://images.unsplash.com/photo-1509042239860-f550ce710b93'
            ]

        ];

        /* START */
        if ($text == '/start') {

            Telegram::sendMessage([
                'chat_id'=>$chat,
                'text'=>"🙏 Welcome to E-Manu Food 🍽\nFounded by Cheahun\n\nPlease choose language:",
                'reply_markup'=>json_encode([
                    'inline_keyboard'=>[
                        [
                            ['text'=>'🇰🇭 Khmer','callback_data'=>'kh'],
                            ['text'=>'🇬🇧 English','callback_data'=>'en']
                        ]
                    ]
                ])
            ]);

        }

        /* LANGUAGE → SHOW MENU */
        elseif ($callback == 'kh' || $callback == 'en') {

            foreach ($menu as $key=>$food) {

                Telegram::sendPhoto([
                    'chat_id'=>$chat,
                    'photo'=>$food['img'],
                    'caption'=>"🍽 ".$food['name']."\nPrice: $".$food['price'],
                    'reply_markup'=>json_encode([
                        'inline_keyboard'=>[
                            [
                                ['text'=>'Select','callback_data'=>$key]
                            ]
                        ]
                    ])
                ]);

            }

        }

        /* FOOD SELECT */
        elseif (isset($menu[$callback])) {

            $food = $menu[$callback];

            $qr = "https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=PAY".$food['price'];

            Telegram::sendPhoto([
                'chat_id'=>$chat,
                'photo'=>$qr,
                'caption'=>"🧾 Order: ".$food['name'].
                          "\nPrice: $".$food['price'].
                          "\n\nPlease scan QR to pay",
                'reply_markup'=>json_encode([
                    'inline_keyboard'=>[
                        [
                            ['text'=>'✅ Paid','callback_data'=>'paid']
                        ]
                    ]
                ])
            ]);

        }

        /* PAYMENT */
        elseif ($callback == 'paid') {

            Telegram::sendMessage([
                'chat_id'=>$chat,
                'text'=>"✅ Payment received (Fake)\n\nThank you for ordering 🙏\n\nClick below to order again:",
                'reply_markup'=>json_encode([
                    'inline_keyboard'=>[
                        [
                            ['text'=>'🍔 Show Menu','callback_data'=>'en']
                        ]
                    ]
                ])
            ]);

        }

        return response()->json(['ok'=>true]);
    }
}
