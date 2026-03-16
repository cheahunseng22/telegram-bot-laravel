<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Telegram\Bot\Laravel\Facades\Telegram;
use Illuminate\Support\Facades\Cache;

class TelegramController extends Controller
{
    public function webhook(Request $request)
    {
        $chat_id = $request['message']['chat']['id'] ?? null;
        $text = strtolower(trim($request['message']['text'] ?? ''));

        if (!$chat_id) {
            return response()->json(['status' => 'ok']);
        }

        // Load user state from cache
        $user = Cache::get("user_$chat_id", [
            'state' => 'start',
            'language' => null,
            'order' => [],
        ]);

        switch ($user['state']) {

            case 'start':

                $user['state'] = 'choose_language';

                Telegram::sendMessage([
                    'chat_id' => $chat_id,
                    'text' => "🙏 Dear valued customer,\nWelcome to E-Manu Food 🍽️\nFounded by Cheahun.\n\nPlease choose your language:\n1️⃣ Khmer\n2️⃣ English"
                ]);

                break;


            case 'choose_language':

                if ($text === '1' || $text === 'khmer') {
                    $user['language'] = 'khmer';
                } 
                elseif ($text === '2' || $text === 'english') {
                    $user['language'] = 'english';
                } 
                else {
                    Telegram::sendMessage([
                        'chat_id' => $chat_id,
                        'text' => "🙏 Please choose:\n1 for Khmer\n2 for English"
                    ]);
                    Cache::put("user_$chat_id", $user, 3600);
                    return response()->json(['status'=>'ok']);
                }

                $user['state'] = 'show_menu';
                $this->sendMenu($chat_id, $user['language']);

                break;


            case 'show_menu':

                $menu = [
                    1 => ['item' => 'Burger', 'price' => 5],
                    2 => ['item' => 'Pizza', 'price' => 7],
                    3 => ['item' => 'Coffee', 'price' => 3],
                ];

                if (isset($menu[(int)$text])) {

                    $item = $menu[(int)$text];

                    $user['order'][] = $item;

                    $total = array_sum(array_column($user['order'], 'price'));

                    Telegram::sendMessage([
                        'chat_id' => $chat_id,
                        'text' => "🙏 You selected: {$item['item']}\nPrice: {$item['price']}$\nTotal: {$total}$\n\nPlease scan Bakong QR to pay.\nType 'paid' after payment."
                    ]);

                    $user['state'] = 'payment';

                } else {

                    $this->sendMenu($chat_id, $user['language']);

                }

                break;


            case 'payment':

                if ($text === 'paid') {

                    Telegram::sendMessage([
                        'chat_id' => $chat_id,
                        'text' => "🙏 Payment successful!\nThank you for ordering with E-Manu Food.\n\nReturning to menu..."
                    ]);

                    $user['order'] = [];
                    $user['state'] = 'show_menu';

                    $this->sendMenu($chat_id, $user['language']);

                } else {

                    Telegram::sendMessage([
                        'chat_id' => $chat_id,
                        'text' => "🙏 Please type 'paid' after completing payment."
                    ]);

                }

                break;


            default:

                $user['state'] = 'start';

                Telegram::sendMessage([
                    'chat_id' => $chat_id,
                    'text' => "🙏 Type /start to begin ordering."
                ]);

        }

        // Save state back to cache
        Cache::put("user_$chat_id", $user, 3600);

        return response()->json(['status' => 'ok']);
    }



    private function sendMenu($chat_id, $language)
    {

        $menu_text = ($language === 'khmer')
            ? "🙏 សូមគោរពអតិថិជន៖\n\n🍔 មឺនុយ E-Manu Food\n1️⃣ បឺហ្គឺ - \$5\n2️⃣ ពីហ្សា - \$7\n3️⃣ កាហ្វេ - \$3\n\nសូមវាយលេខដែលអ្នកចង់បញ្ជាទិញ"
            : "🙏 Dear valued customer:\n\n🍔 E-Manu Food Menu\n1️⃣ Burger - \$5\n2️⃣ Pizza - \$7\n3️⃣ Coffee - \$3\n\nPlease type the number of the item you wish to order";

        Telegram::sendMessage([
            'chat_id' => $chat_id,
            'text' => $menu_text
        ]);
    }
}
