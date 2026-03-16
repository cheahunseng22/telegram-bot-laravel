<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Telegram\Bot\Laravel\Facades\Telegram;
use Illuminate\Support\Facades\Cache;

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

        // Menu items
        $menu = [
            '1'=>['name'=>'🍔 Burger','price'=>5],
            '2'=>['name'=>'🍕 Pizza','price'=>7],
            '3'=>['name'=>'☕ Coffee','price'=>3]
        ];

        $state = Cache::get("state_$chat");
        $lang = Cache::get("lang_$chat","en");

        // ---------------- START ----------------
        if ($callback === 'start' || $text=='/start') {
            Cache::put("state_$chat","choose_lang",3600);
            Telegram::sendMessage([
                'chat_id'=>$chat,
                'text'=>"🙏 Welcome to E-Manu Food 🍽\nFounded by Cheahun\n\nPlease choose language:",
                'reply_markup'=>json_encode([
                    'inline_keyboard'=>[
                        [['text'=>'🇰🇭 Khmer','callback_data'=>'lang_kh'],
                         ['text'=>'🇬🇧 English','callback_data'=>'lang_en']]
                    ]
                ])
            ]);
            return response()->json(['ok'=>true]);
        }

        // ---------------- LANGUAGE SELECTION ----------------
        if ($callback=='lang_kh' || $callback=='lang_en') {
            $lang = $callback=='lang_kh'?'kh':'en';
            Cache::put("lang_$chat",$lang,3600);
            Cache::put("state_$chat","choose_food",3600);

            // Show menu as numbers
            $menu_text = $lang=='kh' ? "🍽 មឺនុយ:\n1 បឺហ្គឺ\n2 ពីហ្សា\n3 កាហ្វេ\n\nសូមវាយលេខដើម្បីបញ្ជាទិញ" 
                                     : "🍽 Menu:\n1 Burger\n2 Pizza\n3 Coffee\n\nType number to order";

            Telegram::sendMessage([
                'chat_id'=>$chat,
                'text'=>$menu_text
            ]);
            return response()->json(['ok'=>true]);
        }

        // ---------------- FOOD SELECTION ----------------
        if ($state=='choose_food' && isset($menu[$text])) {
            $food = $menu[$text];
            Cache::put("order_$chat",$food,3600);
            Cache::put("state_$chat","choose_qty",3600);

            // Quantity buttons 1-5
            $buttons = [];
            for ($i=1;$i<=5;$i++){
                $buttons[]=[['text'=>"$i","callback_data"=>"qty_$i"]];
            }

            Telegram::sendMessage([
                'chat_id'=>$chat,
                'text'=>"🛒 You selected: ".$food['name']."\nPrice: $".$food['price']."\n\nChoose quantity:",
                'reply_markup'=>json_encode(['inline_keyboard'=>$buttons])
            ]);
            return response()->json(['ok'=>true]);
        }

        // ---------------- QUANTITY SELECTION ----------------
        if ($state=='choose_qty' && str_starts_with($callback,'qty_')) {
            $qty = intval(substr($callback,4));
            $food = Cache::get("order_$chat");
            $total = $food['price'] * $qty;
            Cache::put("state_$chat","paid",3600);
            Cache::put("order_$chat",['item'=>$food['name'],'price'=>$food['price'],'qty'=>$qty,'total'=>$total],3600);

            $qr = "https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=PAY-$total";

            Telegram::sendMessage([
                'chat_id'=>$chat,
                'text'=>"🧾 Order: ".$food['name']." x$qty\nTotal: $".$total."\n\nScan fake QR to pay ✅\nAfter payment click Paid",
                'reply_markup'=>json_encode([
                    'inline_keyboard'=>[['text'=>'✅ Paid','callback_data'=>'paid']]
                ])
            ]);
            return response()->json(['ok'=>true]);
        }

        // ---------------- PAID ----------------
        if ($state=='paid' && $callback=='paid') {
            Cache::forget("state_$chat");
            Cache::forget("order_$chat");

            Telegram::sendMessage([
                'chat_id'=>$chat,
                'text'=>"✅ Payment received (Fake)\n\nThank you 🙏\n\nChoose language again:",
                'reply_markup'=>json_encode([
                    'inline_keyboard'=>[
                        [['text'=>'🇰🇭 Khmer','callback_data'=>'lang_kh'],
                         ['text'=>'🇬🇧 English','callback_data'=>'lang_en']]
                    ]
                ])
            ]);
            return response()->json(['ok'=>true]);
        }

        // ---------------- UNKNOWN MESSAGE ----------------
        Telegram::sendMessage([
            'chat_id'=>$chat,
            'text'=>"Type /start to begin ordering 🍽",
            'reply_markup'=>json_encode([
                'inline_keyboard'=>[
                    [['text'=>'Start','callback_data'=>'start']]
                ]
            ])
        ]);

        return response()->json(['ok'=>true]);
    }
}
