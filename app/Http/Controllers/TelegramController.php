<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Telegram\Bot\Laravel\Facades\Telegram;
use Illuminate\Support\Facades\Cache;

class TelegramController extends Controller
{
    public function webhook(Request $request)
    {
        $chat = $request['message']['chat']['id'] ?? null;
        $text = strtolower(trim($request['message']['text'] ?? ''));

        if (!$chat) return response()->json(['ok'=>true]);

        $user = Cache::get("user_$chat", [
            'state'=>'start',
            'lang'=>null
        ]);

        if ($user['state']=='start') {

            Telegram::sendMessage([
                'chat_id'=>$chat,
                'text'=>"🙏 Welcome to E-Manu Food 🍽\nFounded by Cheahun\n\nChoose language:\n1 Khmer\n2 English"
            ]);

            $user['state']='language';

        }

        elseif ($user['state']=='language') {

            if ($text=='1') $user['lang']='kh';
            elseif ($text=='2') $user['lang']='en';
            else {
                Telegram::sendMessage([
                    'chat_id'=>$chat,
                    'text'=>"Please type 1 or 2"
                ]);
                Cache::put("user_$chat",$user,3600);
                return;
            }

            $user['state']='menu';
            $this->menu($chat,$user['lang']);
        }

        elseif ($user['state']=='menu') {

            $menu=[
                1=>['Burger',5],
                2=>['Pizza',7],
                3=>['Coffee',3]
            ];

            if(isset($menu[$text])){

                $item=$menu[$text][0];
                $price=$menu[$text][1];

                Telegram::sendMessage([
                    'chat_id'=>$chat,
                    'text'=>"You selected $item\nPrice: $$price\n\nType 'paid' after payment."
                ]);

                $user['state']='payment';
            }

        }

        elseif ($user['state']=='payment') {

            if($text=='paid'){

                Telegram::sendMessage([
                    'chat_id'=>$chat,
                    'text'=>"✅ Payment received!\nThank you for ordering."
                ]);

                $user['state']='menu';
                $this->menu($chat,$user['lang']);
            }
        }

        Cache::put("user_$chat",$user,3600);

        return response()->json(['ok'=>true]);
    }


    private function menu($chat,$lang)
    {
        $text = $lang=='kh'
        ? "🍔 មឺនុយ\n1 Burger \$5\n2 Pizza \$7\n3 Coffee \$3"
        : "🍔 Menu\n1 Burger \$5\n2 Pizza \$7\n3 Coffee \$3";

        Telegram::sendMessage([
            'chat_id'=>$chat,
            'text'=>$text
        ]);
    }
}
