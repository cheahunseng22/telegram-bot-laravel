<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Telegram\Bot\Laravel\Facades\Telegram;
use Illuminate\Support\Facades\Cache;

class TelegramController extends Controller
{

    public function webhook(Request $request)
    {

        $msg = $request->input('message') ?? $request->input('callback_query');

        if (!$msg) {
            return response()->json(['status'=>'ok']);
        }

        $callback = isset($msg['data']);

        $chat = $callback ? $msg['message']['chat']['id'] : $msg['chat']['id'];
        $text = $callback ? $msg['data'] : ($msg['text'] ?? '');
        $text = strtolower(trim($text));

        $name = $callback ? $msg['from']['first_name'] : ($msg['from']['first_name'] ?? 'Friend');

        $state = Cache::get("state_$chat");
        $lang  = Cache::get("lang_$chat", "en");


        // Restaurant Menu
        $menu = [

            1 => ['☕ Coffee', [
                'americano'=>5,
                'latte'=>5,
                'cappuccino'=>6,
                'mocha'=>6
            ]],

            2 => ['🍵 Tea', [
                'green tea'=>3,
                'black tea'=>3,
                'oolong'=>4
            ]],

            3 => ['🥪 Sandwich', [
                'chicken'=>7,
                'veggie'=>6,
                'ham'=>7
            ]],

            4 => ['🧃 Juice', [
                'orange'=>4,
                'apple'=>4,
                'mango'=>5
            ]],

            5 => ['🍰 Dessert', [
                'cake'=>5,
                'brownie'=>4,
                'ice cream'=>3
            ]],

            6 => ['🍿 Snacks', [
                'chips'=>2,
                'cookie'=>3,
                'nuts'=>4
            ]],

        ];


        /*
        =================================
        GREETING RESPONSE
        =================================
        */

        $greetings = [
            'hi','hello','hey','yo','good morning',
            'good evening','wassup','sup','សួស្តី'
        ];

        if(in_array($text,$greetings)){

            $reply = $lang=="kh"
                ? "🙏 សួស្តី $name\nសូមស្វាគមន៍មកកាន់ភោជនីយដ្ឋានយើងខ្ញុំ 🍽\nបង្កើតដោយ Cheahun\n\nវាយ menu ដើម្បីមើលម៉ឺនុយ"
                : "🙏 Hello $name,\nWelcome to my restaurant 🍽\nFounded by Cheahun.\n\nType menu to see our food menu.";

            Telegram::sendMessage([
                'chat_id'=>$chat,
                'text'=>$reply
            ]);

            return;
        }


        /*
        =================================
        HELP COMMAND
        =================================
        */

        if($text=="/help" || ($callback && $text=='help')){

            $reply = $lang=="kh"
                ? "📘 របៀបបញ្ជាទិញ:\n\n1️⃣ វាយ /start\n2️⃣ ជ្រើសភាសា\n3️⃣ ជ្រើសប្រភេទម្ហូប\n4️⃣ ជ្រើសមុខម្ហូប\n5️⃣ ជ្រើសចំនួន\n6️⃣ ស្កេន QR បង់ប្រាក់\n7️⃣ ចុច 💳 Paid\n\nវាយ menu ដើម្បីបញ្ជាទិញម្ដងទៀត"
                : "📘 How to order:\n\n1️⃣ Type /start\n2️⃣ Choose language\n3️⃣ Select category\n4️⃣ Select food item\n5️⃣ Choose quantity\n6️⃣ Scan QR to pay\n7️⃣ Tap 💳 Paid\n\nType menu to order again";

            Telegram::sendMessage([
                'chat_id'=>$chat,
                'text'=>$reply
            ]);

            return;
        }


        /*
        =================================
        START OR MENU
        =================================
        */

        if($text=="/start" || $text=="menu"){

            Cache::forget("state_$chat");
            Cache::forget("lang_$chat");
            Cache::forget("order_$chat");
            Cache::forget("cat_$chat");

            Telegram::sendMessage([

                'chat_id'=>$chat,

                'text'=>"👋 Hello $name\nWelcome to e-Menu Order Restaurant 🍽\nFounded by Cheahun\n\nPlease choose your language:",

                'reply_markup'=>json_encode([
                    'inline_keyboard'=>[

                        [
                            ['text'=>"🇬🇧 English",'callback_data'=>"lang_en"]
                        ],

                        [
                            ['text'=>"🇰🇭 Khmer",'callback_data'=>"lang_kh"]
                        ],

                        [
                            ['text'=>"❓ Help",'callback_data'=>"help"]
                        ]

                    ]
                ])

            ]);

            Cache::put("state_$chat","language",3600);

            return;
        }



        /*
        =================================
        CALLBACK BUTTON HANDLER
        =================================
        */

        if($callback){

            /*
            ---------------------------
            LANGUAGE
            ---------------------------
            */

            if(str_starts_with($text,'lang_')){

                $language = explode('_',$text)[1];

                Cache::put("lang_$chat",$language,3600);
                Cache::put("state_$chat","category",3600);

                $buttons = [

                    [
                        ['text'=>'☕ Coffee','callback_data'=>'cat_1'],
                        ['text'=>'🍵 Tea','callback_data'=>'cat_2']
                    ],

                    [
                        ['text'=>'🥪 Sandwich','callback_data'=>'cat_3'],
                        ['text'=>'🧃 Juice','callback_data'=>'cat_4']
                    ],

                    [
                        ['text'=>'🍰 Dessert','callback_data'=>'cat_5'],
                        ['text'=>'🍿 Snacks','callback_data'=>'cat_6']
                    ]

                ];

                Telegram::sendMessage([

                    'chat_id'=>$chat,

                    'text'=>$language=="kh"
                        ? "📋 ម៉ឺនុយ\nសូមជ្រើសប្រភេទម្ហូប"
                        : "📋 MENU\nPlease choose category",

                    'reply_markup'=>json_encode([
                        'inline_keyboard'=>$buttons
                    ])

                ]);

                return;
            }



            /*
            ---------------------------
            CATEGORY
            ---------------------------
            */

            if(str_starts_with($text,'cat_')){

                $cat_id = intval(explode('_',$text)[1]);

                Cache::put("cat_$chat",$cat_id,3600);
                Cache::put("state_$chat","item",3600);

                $items = $menu[$cat_id][1];

                $buttons=[];

                foreach($items as $item=>$price){

                    $buttons[]=[
                        ['text'=>"💰 $item - \$$price",'callback_data'=>"item_$item"]
                    ];

                }

                Telegram::sendMessage([

                    'chat_id'=>$chat,

                    'text'=>$lang=="kh"
                        ? "🍽 សូមជ្រើសមុខម្ហូប"
                        : "🍽 Choose item",

                    'reply_markup'=>json_encode([
                        'inline_keyboard'=>$buttons
                    ])

                ]);

                return;
            }



            /*
            ---------------------------
            ITEM
            ---------------------------
            */

            if(str_starts_with($text,'item_')){

                $item_name = substr($text,5);

                $cat_id = Cache::get("cat_$chat");

                $price = $menu[$cat_id][1][$item_name] ?? 0;

                Cache::put("order_$chat",[
                    'item'=>$item_name,
                    'price'=>$price
                ],3600);

                Cache::put("state_$chat","qty",3600);


                $emoji=['1️⃣','2️⃣','3️⃣','4️⃣','5️⃣','6️⃣','7️⃣','8️⃣','9️⃣','🔟'];

                $buttons=[];

                foreach(range(1,10) as $i){

                    $buttons[]=[
                        ['text'=>$emoji[$i-1],'callback_data'=>"qty_$i"]
                    ];

                }


                Telegram::sendMessage([

                    'chat_id'=>$chat,

                    'text'=>"🛒 $item_name selected\nChoose quantity",

                    'reply_markup'=>json_encode([
                        'inline_keyboard'=>$buttons
                    ])

                ]);

                return;
            }



            /*
            ---------------------------
            QUANTITY
            ---------------------------
            */

            if(str_starts_with($text,'qty_')){

                $quantity = intval(substr($text,4));

                $order = Cache::get("order_$chat");

                $total = $order['price'] * $quantity;

                $qr="https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=PAY-$total";

                Cache::put("state_$chat","payment",3600);


                Telegram::sendMessage([

                    'chat_id'=>$chat,

                    'text'=>"💳 Payment Required\n\n{$order['item']} x$quantity\nTotal: \$$total\n\nScan QR:\n$qr\n\nThen tap Paid",

                    'reply_markup'=>json_encode([
                        'inline_keyboard'=>[
                            [
                                ['text'=>"💳 Paid",'callback_data'=>"paid"]
                            ]
                        ]
                    ])

                ]);

                return;
            }



            /*
            ---------------------------
            PAYMENT CONFIRM
            ---------------------------
            */

            if($text=='paid'){

                Telegram::sendMessage([

                    'chat_id'=>$chat,

                    'text'=>"🎉 Payment Successful!\n\nYour food is being prepared ☕🍰\n\nType menu to order again."

                ]);

                Cache::forget("state_$chat");
                Cache::forget("order_$chat");

                return;
            }

        }



        /*
        =================================
        UNKNOWN MESSAGE
        =================================
        */

        Telegram::sendMessage([

            'chat_id'=>$chat,

            'text'=>$lang=="kh"
                ? "🤔 ខ្ញុំមិនយល់សារនេះ\nវាយ /start ឬ /help"
                : "🤔 I didn't understand\nType /start or /help"

        ]);

    }

}
