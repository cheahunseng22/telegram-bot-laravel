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

        if (!$chat) return response()->json(['ok'=>true]);

        $text = strtolower(trim($request['message']['text'] ?? ''));
        $callback = $request['callback_query']['data'] ?? null;

        // MENU
        $menu = [
            '1'=>['name'=>'🍔 Burger','price'=>5],
            '2'=>['name'=>'🍕 Pizza','price'=>7],
            '3'=>['name'=>'☕ Coffee','price'=>3]
        ];

        // ---------------- START ----------------
        if ($text == '/start' || $callback == 'start') {

            Telegram::sendMessage([
                'chat_id'=>$chat,
                'text'=>"🙏 Welcome to E-Manu Food 🍽\n\nChoose language:",
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

        // ---------------- LANGUAGE ----------------
        if ($callback == 'lang_kh' || $callback == 'lang_en') {

            $menu_text = $callback == 'lang_kh'
                ? "🍽 មឺនុយ:\n1 បឺហ្គឺ\n2 ពីហ្សា\n3 កាហ្វេ"
                : "🍽 Menu:\n1 Burger\n2 Pizza\n3 Coffee";

            // buttons for food
            $buttons = [];
            foreach ($menu as $key=>$item){
                $buttons[] = [
                    ['text'=>$item['name'], 'callback_data'=>"food_$key"]
                ];
            }

            Telegram::sendMessage([
                'chat_id'=>$chat,
                'text'=>$menu_text,
                'reply_markup'=>json_encode([
                    'inline_keyboard'=>$buttons
                ])
            ]);

            return response()->json(['ok'=>true]);
        }

        // ---------------- FOOD SELECT ----------------
        if ($callback && str_starts_with($callback,'food_')) {

            $id = explode('_',$callback)[1];
            $food = $menu[$id];

            $buttons = [];
            for ($i=1;$i<=5;$i++){
                $buttons[] = [
                    ['text'=>"$i", 'callback_data'=>"qty_{$id}_{$i}"]
                ];
            }

            Telegram::sendMessage([
                'chat_id'=>$chat,
                'text'=>"🛒 ".$food['name']." ($".$food['price'].")\nChoose quantity:",
                'reply_markup'=>json_encode([
                    'inline_keyboard'=>$buttons
                ])
            ]);

            return response()->json(['ok'=>true]);
        }

        // ---------------- QTY ----------------
        if ($callback && str_starts_with($callback,'qty_')) {

            [$type,$id,$qty] = explode('_',$callback);

            $food = $menu[$id];
            $total = $food['price'] * $qty;

            Telegram::sendMessage([
                'chat_id'=>$chat,
                'text'=>"🧾 ".$food['name']." x$qty\nTotal: $".$total,
                'reply_markup'=>json_encode([
                    'inline_keyboard'=>[
                        [
                            ['text'=>'✅ Paid','callback_data'=>"paid_{$id}_{$qty}"]
                        ]
                    ]
                ])
            ]);

            return response()->json(['ok'=>true]);
        }

        // ---------------- PAID ----------------
        if ($callback && str_starts_with($callback,'paid_')) {

            Telegram::sendMessage([
                'chat_id'=>$chat,
                'text'=>"✅ Payment received (Fake)\n\nThank you 🙏",
                'reply_markup'=>json_encode([
                    'inline_keyboard'=>[
                        [
                            ['text'=>'Start Again','callback_data'=>'start']
                        ]
                    ]
                ])
            ]);

            return response()->json(['ok'=>true]);
        }

        // ---------------- DEFAULT ----------------
        Telegram::sendMessage([
            'chat_id'=>$chat,
            'text'=>"Type /start to begin 🍽"
        ]);

        return response()->json(['ok'=>true]);
    }
}
