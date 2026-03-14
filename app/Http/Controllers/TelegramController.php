<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Telegram\Bot\Laravel\Facades\Telegram;
use Illuminate\Support\Facades\Cache;

class TelegramController extends Controller
{
    public function webhook(Request $request)
    {

         file_put_contents(storage_path('logs/telegram.log'), print_r($request->all(), true), FILE_APPEND);
        // Get message or callback query
        $msg = $request->input('message') ?? $request->input('callback_query');
        if (!$msg) return;

        $callback = isset($msg['data']);
        $chat = $callback ? $msg['message']['chat']['id'] : $msg['chat']['id'];
        $text = $callback ? $msg['data'] : ($msg['text'] ?? '');
        $text = strtolower(trim($text));
        $name = $callback ? $msg['from']['first_name'] : ($msg['from']['first_name'] ?? 'friend');

        $state = Cache::get("state_$chat");
        $lang  = Cache::get("lang_$chat", "en");

        // Menu with icons
        $menu = [
            1 => ['☕ Coffee', ['Americano'=>5,'Latte'=>5,'Cappuccino'=>6,'Mocha'=>6]],
            2 => ['🍵 Tea', ['Green Tea'=>3,'Black Tea'=>3,'Oolong'=>4]],
            3 => ['🥪 Sandwich', ['Chicken'=>7,'Veggie'=>6,'Ham'=>7]],
            4 => ['🧃 Juice', ['Orange'=>4,'Apple'=>4,'Mango'=>5]],
            5 => ['🍰 Dessert', ['Cake'=>5,'Brownie'=>4,'Ice Cream'=>3]],
            6 => ['🍿 Snacks', ['Chips'=>2,'Cookie'=>3,'Nuts'=>4]],
        ];

        // -------------------- GREETINGS --------------------
        $greetings = ['hi','hello','hey','yo','good morning','good evening','wassup','sup','hell','សួស្តី'];
        if(in_array($text,$greetings)){
            $reply = $lang=="kh"
                ? "😊 សួស្តី $name!\nចុច menu ដើម្បីមើលម៉ឺនុយ"
                : "😊 Hello $name!\nClick menu to see our food menu";
            Telegram::sendMessage(['chat_id'=>$chat, 'text'=>$reply]);
            return;
        }

        // -------------------- HELP --------------------
        if($text=="/help" || ($callback && $text=='help')){
            $reply = $lang=="kh"
                ? "📝 ជួយអ្នកបញ្ជាទិញ:\n1️⃣ ចាប់ផ្តើម /start\n2️⃣ ជ្រើសភាសា\n3️⃣ ជ្រើសប្រភេទ\n4️⃣ ជ្រើសមុខម្ហូប\n5️⃣ ជ្រើសចំនួន\n6️⃣ បន្ទាប់ពីបង់ប្រាក់ ចុច 💳 Paid\n7️⃣ វាយ menu ដើម្បីបញ្ជាទិញម្ដងទៀត"
                : "📝 Help guide:\n1️⃣ Start with /start\n2️⃣ Choose language\n3️⃣ Select category\n4️⃣ Click item\n5️⃣ Choose quantity\n6️⃣ After payment, tap 💳 Paid\n7️⃣ Type menu to order again";
            Telegram::sendMessage(['chat_id'=>$chat,'text'=>$reply]);
            return;
        }

        // -------------------- START / MENU --------------------
        if($text=="/start" || $text=="menu"){
            // Clear previous state
            Cache::forget("state_$chat");
            Cache::forget("lang_$chat");
            Cache::forget("order_$chat");
            Cache::forget("cat_$chat");

            Telegram::sendMessage([
                'chat_id'=>$chat,
                'text'=>"👋 Hello $name!\nWelcome to e-manu order restaurant 🍽\nFounded by Cheahun.\n\nPlease choose your language:",
                'reply_markup'=>json_encode([
                    'inline_keyboard'=>[
                        [['text'=>"🇬🇧 English",'callback_data'=>"lang_en"]],
                        [['text'=>"🇰🇭 Khmer",'callback_data'=>"lang_kh"]],
                        [['text'=>"❓ Help",'callback_data'=>"help"]]
                    ]
                ])
            ]);

            Cache::put("state_$chat","language",3600);
            return;
        }

        // -------------------- CALLBACK HANDLER --------------------
        if($callback){

            // -------------------- LANGUAGE --------------------
            if(str_starts_with($text,'lang_')){
                $language = explode('_',$text)[1];
                Cache::put("lang_$chat",$language,3600);
                Cache::put("state_$chat","category",3600);

                // Categories buttons (2 per row)
                $inline_buttons = [
                    [['text'=>'☕ Coffee','callback_data'=>'cat_1'], ['text'=>'🍵 Tea','callback_data'=>'cat_2']],
                    [['text'=>'🥪 Sandwich','callback_data'=>'cat_3'], ['text'=>'🧃 Juice','callback_data'=>'cat_4']],
                    [['text'=>'🍰 Dessert','callback_data'=>'cat_5'], ['text'=>'🍿 Snacks','callback_data'=>'cat_6']],
                ];

                Telegram::sendMessage([
                    'chat_id'=>$chat,
                    'text'=>$language=="kh" ? "📋 ម៉ឺនុយ\nជ្រើសប្រភេទ:" : "📋 MENU\nChoose category:",
                    'reply_markup'=>json_encode(['inline_keyboard'=>$inline_buttons])
                ]);
                return;
            }

            // -------------------- CATEGORY --------------------
            if(str_starts_with($text,'cat_')){
                $cat_id = intval(explode('_',$text)[1]);
                Cache::put("cat_$chat",$cat_id,3600);
                Cache::put("state_$chat","item",3600);

                $items = $menu[$cat_id][1];
                $inline_buttons = [];
                foreach($items as $item=>$price){
                    $inline_buttons[] = [['text'=>"💰 $item - \$$price",'callback_data'=>"item_$item"]];
                }

                Telegram::sendMessage([
                    'chat_id'=>$chat,
                    'text'=>$lang=="kh" ? "🍽 ជ្រើសមុខម្ហូប:" : "🍽 Choose item:",
                    'reply_markup'=>json_encode(['inline_keyboard'=>$inline_buttons])
                ]);
                return;
            }

            // -------------------- ITEM --------------------
            if(str_starts_with($text,'item_')){
                $item_name = substr($text,5);
                $cat_id = Cache::get("cat_$chat");
                $price = $menu[$cat_id][1][$item_name] ?? 0;

                Cache::put("order_$chat",['item'=>$item_name,'price'=>$price],3600);
                Cache::put("state_$chat","qty",3600);

                // Quantity buttons
                $qty_emoji = ['1️⃣','2️⃣','3️⃣','4️⃣','5️⃣','6️⃣','7️⃣','8️⃣','9️⃣','🔟'];
                $inline_buttons = [];
                foreach(range(1,10) as $i){
                    $inline_buttons[] = [['text'=>$qty_emoji[$i-1],'callback_data'=>"qty_$i"]];
                }

                Telegram::sendMessage([
                    'chat_id'=>$chat,
                    'text'=>$lang=="kh" ? "🛒 អ្នកជ្រើស $item_name\nជ្រើសចំនួន (1-10):" : "🛒 You selected: $item_name\nChoose quantity (1-10):",
                    'reply_markup'=>json_encode(['inline_keyboard'=>$inline_buttons])
                ]);
                return;
            }

            // -------------------- QUANTITY --------------------
            if(str_starts_with($text,'qty_')){
                $quantity = intval(substr($text,4));
                $order = Cache::get("order_$chat");
                $total = $order['price'] * $quantity;
                $qr = "https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=BAKONG-$total";

                Cache::put("state_$chat","payment",3600);

                Telegram::sendMessage([
                    'chat_id'=>$chat,
                    'text'=>$lang=="kh"
                        ? "💳 សូមបង់ប្រាក់\n\n{$order['item']} x$quantity\nសរុប: \$$total\n\nស្កេន QR:\n$qr\n\nបន្ទាប់មកចុច 💳 Paid"
                        : "💳 Payment required\n\n{$order['item']} x$quantity\nTotal: \$$total\n\nScan QR:\n$qr\n\nThen tap 💳 Paid",
                    'reply_markup'=>json_encode([
                        'inline_keyboard'=>[['text'=>"💳 Paid",'callback_data'=>'paid']]
                    ])
                ]);
                return;
            }

            // -------------------- PAYMENT --------------------
            if($text=='paid'){
                $reply = $lang=="kh"
                    ? "🎉 បង់ប្រាក់ជោគជ័យ!\n\nម្ហូបកំពុងរៀបចំ ☕\n\nចុច menu ដើម្បីបញ្ជាទិញម្ដងទៀត"
                    : "🎉 Payment Successful!\n\nYour order is being prepared ☕\n\nClick menu to order again";

                Telegram::sendMessage(['chat_id'=>$chat,'text'=>$reply]);
                Cache::forget("state_$chat");
                Cache::forget("order_$chat");
                return;
            }

            // HELP BUTTON
            if($text=="help"){
                $msg = $lang=="kh" ? "📝 សូមចុច menu ដើម្បីចាប់ផ្តើមបញ្ជាទិញ" : "📝 Click menu to start ordering";
                Telegram::sendMessage(['chat_id'=>$chat,'text'=>$msg]);
                return;
            }
        }

        // -------------------- UNKNOWN MESSAGE --------------------
        Telegram::sendMessage([
            'chat_id'=>$chat,
            'text'=>$lang=="kh" ? "🤔 ខ្ញុំមិនយល់សារនេះ\nវាយ /start ឬ /help" : "🤔 I didn't understand\nType /start or /help"
        ]);
    }

    // Call this once during bot setup
    public static function setCommands()
    {
        Telegram::setMyCommands([
            ['command' => 'start', 'description' => 'Start ordering food'],
            ['command' => 'menu', 'description' => 'Show menu categories'],
            ['command' => 'help', 'description' => 'Show help guide']
        ]);
    }
}
