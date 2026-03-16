<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Telegram\Bot\Laravel\Facades\Telegram;

class TelegramController extends Controller
{
    public function webhook(Request $request)
    {
        $chat = $request['message']['chat']['id'] ?? $request['callback_query']['message']['chat']['id'] ?? null;
        $text = strtolower(trim($request['message']['text'] ?? ''));
        $callback = $request['callback_query']['data'] ?? null;

        if (!$chat) return response()->json(['ok'=>true]);

        // Menu with emoji
        $menu = [
            'burger'=>['name'=>'🍔 Burger','price'=>5],
            'pizza'=>['name'=>'🍕 Pizza','price'=>7],
            'coffee'=>['name'=>'☕ Coffee','price'=>3],
        ];

        // START
        if ($text == '/start') {
            Telegram::sendMessage([
                'chat_id'=>$chat,
                'text'=>"🙏 Welcome to E-Manu Food 🍽\nFounded by Cheahun\n\nPlease choose language:",
                'reply_markup'=>json_encode([
                    'inline_keyboard'=>[
                        [
                            ['text'=>'🇰🇭 Khmer','callback_data'=>'lang_kh'],
                            ['text'=>'🇬🇧 English','callback_data'=>'lang_en']
                        ]
                    ]
                ])
            ]);
        }

        // CALLBACK HANDLER
        elseif ($callback) {

            // Acknowledge callback query
            Telegram::answerCallbackQuery([
                'callback_query_id'=>$request['callback_query']['id']
            ]);

            // Language selected → show menu
            if ($callback=='lang_kh' || $callback=='lang_en') {
                $lang = $callback=='lang_kh'?'kh':'en';
                $buttons = [];
                foreach ($menu as $key=>$item) {
                    $buttons[] = [['text'=>$item['name']." - $".$item['price'],'callback_data'=>$key]];
                }
                Telegram::sendMessage([
                    'chat_id'=>$chat,
                    'text'=> $lang=='kh'?"🍽 ជ្រើសម្ហូប:":"🍽 Choose your food:",
                    'reply_markup'=>json_encode(['inline_keyboard'=>$buttons])
                ]);
            }

            // Food selected → show QR
            elseif (isset($menu[$callback])) {
                $food = $menu[$callback];
                $qr = "https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=PAY".$food['price'];
                Telegram::sendMessage([
                    'chat_id'=>$chat,
                    'text'=>"🧾 Order: ".$food['name']."\nPrice: $".$food['price']."\n\nScan this QR to pay ✅\nThen click Paid",
                    'reply_markup'=>json_encode([
                        'inline_keyboard'=>[['text'=>'✅ Paid','callback_data'=>'paid']]
                    ])
                ]);
            }

            // Paid → back to language select
            elseif ($callback=='paid') {
                Telegram::sendMessage([
                    'chat_id'=>$chat,
                    'text'=>"✅ Payment received (Fake)\n\nThank you for ordering 🙏\n\nChoose language again:",
                    'reply_markup'=>json_encode([
                        'inline_keyboard'=>[
                            [
                                ['text'=>'🇰🇭 Khmer','callback_data'=>'lang_kh'],
                                ['text'=>'🇬🇧 English','callback_data'=>'lang_en']
                            ]
                        ]
                    ])
                ]);
            }
        }

        // UNKNOWN MESSAGE
        else {
            Telegram::sendMessage([
                'chat_id'=>$chat,
                'text'=>"Type /start to begin ordering 🍽"
            ]);
        }

        return response()->json(['ok'=>true]);
    }
}
