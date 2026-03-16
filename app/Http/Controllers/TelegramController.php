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

        $menu = [
            '1' => ['Burger',5],
            '2' => ['Pizza',7],
            '3' => ['Coffee',3],
        ];

        /* START */
        if ($text == '/start') {

            Telegram::sendMessage([
                'chat_id'=>$chat,
                'text'=>"🙏 Welcome to E-Manu Food 🍽\nFounded by Cheahun\n\nChoose language:\n1 Khmer 🇰🇭\n2 English 🇬🇧"
            ]);

        }

        /* KHMER MENU */
        elseif ($text == '1') {

            Telegram::sendMessage([
                'chat_id'=>$chat,
                'text'=>"🍔 មឺនុយ\n1 បឺហ្គឺ \$5\n2 ពីហ្សា \$7\n3 កាហ្វេ \$3\n\nសូមវាយលេខដើម្បីបញ្ជាទិញ"
            ]);

        }

        /* ENGLISH MENU */
        elseif ($text == '2') {

            Telegram::sendMessage([
                'chat_id'=>$chat,
                'text'=>"🍔 Menu\n1 Burger - \$5\n2 Pizza - \$7\n3 Coffee - \$3\n\nType number to order."
            ]);

        }

        /* ORDER */
        elseif (isset($menu[$text])) {

            $item = $menu[$text][0];
            $price = $menu[$text][1];

            $qr = "https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=PAY-$price";

            Telegram::sendMessage([
                'chat_id'=>$chat,
                'text'=>"🧾 Order: $item\nPrice: $$price\n\nScan this Fake QR to pay:\n$qr\n\nAfter payment type: paid"
            ]);

        }

        /* PAYMENT CONFIRM */
        elseif ($text == 'paid') {

            Telegram::sendMessage([
                'chat_id'=>$chat,
                'text'=>"✅ Payment received (Fake)\n\nThank you for ordering 🙏\n\nChoose language again:\n1 Khmer 🇰🇭\n2 English 🇬🇧"
            ]);

        }

        /* UNKNOWN */
        else {

            Telegram::sendMessage([
                'chat_id'=>$chat,
                'text'=>"Type /start to begin ordering 🍽"
            ]);

        }

        return response()->json(['ok'=>true]);
    }
}
