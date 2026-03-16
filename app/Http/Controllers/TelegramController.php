<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Telegram\Bot\Laravel\Facades\Telegram;

class TelegramController extends Controller
{
    private $users = [];

    public function webhook(Request $request)
    {
        $chat_id = $request['message']['chat']['id'] ?? null;
        $text = $request['message']['text'] ?? '';

        if (!$chat_id) {
            return response()->json(['status' => 'ok']);
        }

        if (!isset($this->users[$chat_id])) {
            $this->users[$chat_id] = [
                'state' => 'start',
                'language' => null,
                'order' => [],
            ];
        }

        $user = &$this->users[$chat_id];

        switch ($user['state']) {

            case 'start':
                $user['state'] = 'choose_language';
                Telegram::sendMessage([
                    'chat_id' => $chat_id,
                    'text' => "🙏 Dear valued customer,\nWelcome to **E-Manu Food**, founded by Cheahun! 🍽️\n\nPlease choose your language:\n1️⃣ Khmer\n2️⃣ English"
                ]);
                break;

            case 'choose_language':
                if ($text === '1' || strtolower($text) === 'khmer') {
                    $user['language'] = 'khmer';
                } elseif ($text === '2' || strtolower($text) === 'english') {
                    $user['language'] = 'english';
                } else {
                    Telegram::sendMessage([
                        'chat_id' => $chat_id,
                        'text' => "🙏 Please kindly choose 1 for Khmer or 2 for English"
                    ]);
                    break;
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
                        'text' => "🙏 You have chosen: {$item['item']}\nPrice: {$item['price']} $\nTotal: $total $\n\nPlease scan this **fake Bakong QR** for payment ✅\nType 'paid' after payment"
                    ]);

                    $user['state'] = 'payment';
                } else {
                    $this->sendMenu($chat_id, $user['language']);
                }
                break;

            case 'payment':
                if (strtolower($text) === 'paid') {
                    Telegram::sendMessage([
                        'chat_id' => $chat_id,
                        'text' => "🙏 Payment successful! Thank you for your kind patronage.\nReturning to menu..."
                    ]);
                    $user['order'] = [];
                    $user['state'] = 'show_menu';
                    $this->sendMenu($chat_id, $user['language']);
                } else {
                    Telegram::sendMessage([
                        'chat_id' => $chat_id,
                        'text' => "🙏 Kindly type 'paid' after completing the payment."
                    ]);
                }
                break;

            default:
                $user['state'] = 'start';
                Telegram::sendMessage([
                    'chat_id' => $chat_id,
                    'text' => "🙏 Type /start to begin ordering with respect and courtesy."
                ]);
        }

        return response()->json(['status' => 'ok']);
    }

    private function sendMenu($chat_id, $language)
    {
        $menu_text = ($language === 'khmer')
            ? "🙏 សូមគោរពអតិថិជន៖\n🍔 មឺនុយ E-Manu Food:\n1. បឺហ្គឺ - $5\n2. ពីហ្សា - $7\n3. កាហ្វេ - $3\nសូមវាយលេខដែលអ្នកចង់បញ្ជាទិញ"
            : "🙏 Dear valued customer:\n🍔 E-Manu Food Menu:\n1. Burger - $5\n2. Pizza - $7\n3. Coffee - $3\nPlease type the number of the item you wish to order";

        Telegram::sendMessage([
            'chat_id' => $chat_id,
            'text' => $menu_text
        ]);
    }
}
