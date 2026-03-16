<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Telegram\Bot\Laravel\Facades\Telegram;

class TelegramController extends Controller
{
    public function webhook(Request $request)
    {
        $chat = $request['message']['chat']['id'] ?? null;
        $text = strtolower(trim($request['message']['text'] ?? ''));

        if (!$chat) {
            return response()->json(['ok'=>true]);
        }

        // MENU
        $menu = [
            '1' => ['Burger',5],
            '2' => ['Pizza',7],
            '3' => ['Coffee',3],
        ];

        // START
        if ($text == '/start') {

            Telegram::sendMessage([
                'chat_id'=>$chat,
                'text'=>"🙏 Welcome to E-Manu Food 🍽\nFounded by Cheahun\n\nMenu:\n1 Burger \$5\n2 Pizza \$7\n3 Coffee \$3\n\nType number to order."
            ]);

        }

        // ORDER
        elseif (isset($menu[$text])) {

            $item = $menu[$text][0];
            $price = $menu[$text][1];

            Telegram::sendMessage([
                'chat_id'=>$chat,
                'text'=>"You ordered: $item\nPrice: $$price\n\nType /start to order again."
            ]);

        }

        // UNKNOWN
        else {

            Telegram::sendMessage([
                'chat_id'=>$chat,
                'text'=>"Type /start to see menu."
            ]);

        }

        return response()->json(['ok'=>true]);
    }
}
