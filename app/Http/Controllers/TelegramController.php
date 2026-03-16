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
            '1'=>['Burger',5],
            '2'=>['Pizza',7],
            '3'=>['Coffee',3]
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

        /* LANGUAGE BUTTON */
        elseif ($callback == 'kh') {

            Telegram::sendMessage([
                'chat_id'=>$chat,
                'text'=>"🍔 មឺនុយ\n1 បឺហ្គឺ \$5\n2 ពីហ្សា \$7\n3 កាហ្វេ \$3\n\nសូមវាយលេខដើម្បីបញ្ជាទិញ"
            ]);

        }

        elseif ($callback == 'en') {

            Telegram::sendMessage([
                'chat_id'=>$chat,
                'text'=>"🍔 Menu\n1 Burger \$5\n2 Pizza \$7\n3 Coffee \$3\n\nType number to order."
            ]);

        }

        /* ORDER */
        elseif (isset($menu[$text])) {

            $item = $menu[$text][0];
            $price = $menu[$text][1];

            $qr = "https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=PAY-$price";

            Telegram::sendMessage([
                'chat_id'=>$chat,
                'text'=>"🧾 Order: $item\nPrice: $$price\n\nScan Fake QR:\n$qr\n\nType 'paid' after payment"
            ]);

        }

        /* PAYMENT */
        elseif ($text == 'paid') {

            Telegram::sendMessage([
                'chat_id'=>$chat,
                'text'=>"✅ Payment received (Fake)\n\nThank you 🙏\n\nPlease choose language again:",
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

        return response()->json(['ok'=>true]);
    }
}
