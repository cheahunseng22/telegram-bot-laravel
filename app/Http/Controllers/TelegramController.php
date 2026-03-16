<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Telegram\Bot\Laravel\Facades\Telegram;
use Illuminate\Support\Facades\Cache;

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
            Cache::put("state_$chat", "choose_lang", 3600);
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
            return response()->json(['ok'=>true]);
        }

        // CALLBACK HANDLER
        if ($callback) {

            // Acknowledge callback
            Telegram::answerCallbackQuery([
                'callback_query_id'=>$request['callback_query']['id']
            ]);

            $state = Cache::get("state_$chat");

            // Language selected → show menu
            if ($state == 'choose_lang' && ($callback=='lang_kh' || $callback=='lang_en')) {
                $lang = $callback=='lang_kh'?'kh':'en';
                Cache::put("state_$chat", "choose_food", 3600);
                Cache::put("lang_$chat", $lang, 3600);

                $buttons = [];
                foreach ($menu as $key=>$item) {
                    $buttons[] = [['text'=>$item['name']." - $".$item['price'],'callback_data'=>$key]];
                }

                Telegram::sendMessage([
                    'chat_id'=>$chat,
                    'text'=> $lang=='kh'?"🍽 ជ្រើសម្ហូប:":"🍽 Choose your food:",
                    'reply_markup'=>json_encode(['inline_keyboard'=>$buttons])
                ]);
                return response()->json(['ok'=>true]);
            }

            // Food selected → show QR
            if ($state == 'choose_food' && isset($menu[$callback])) {
                $food = $menu[$callback];
                Cache::put("state_$chat","paid",3600);
                Cache::put("order_$chat",$food,3600);

                $qr = "https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=PAY".$food['price'];
                Telegram::sendMessage([
                    'chat_id'=>$chat,
                    'text'=>"🧾 Order: ".$food['name']."\nPrice: $".$food['price']."\n\nScan this QR to pay ✅\nThen click Paid",
                    'reply_markup'=>json_encode([
                        'inline_keyboard'=>[['text'=>'✅ Paid','callback_data'=>'paid']]
                    ])
                ]);
                return response()->json(['ok'=>true]);
            }

            // Paid → back to language select
            if ($callback=='paid' && $state=='paid') {
                Cache::forget("state_$chat");
                Cache::forget("order_$chat");
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
                return response()->json(['ok'=>true]);
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
