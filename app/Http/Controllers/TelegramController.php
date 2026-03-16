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

        // Initialize user session if not exists
        if (!isset($this->users[$chat_id])) {
            $this->users[$chat_id] = [
                'state' => 'start',
                'language' => null,
                'order' => [],
            ];
        }

        $user = &$this->users[$chat_id];

        // Handle /start command
        if ($text === '/start') {
            $user['state'] = 'start';
            $user['language'] = null;
            $user['order'] = [];
        }

        switch ($user['state']) {
            case 'start':
                $user['state'] = 'choose_language';
                Telegram::sendMessage([
                    'chat_id' => $chat_id,
                    'text' => "🙏 Dear valued customer,\nWelcome to **E-Manu Food**, founded by Cheahun! 🍽️\n\nPlease choose your language:\n1️⃣ Khmer\n2️⃣ English"
                ]);
                break;

            case 'choose_language':
                if ($text === '1' || strtolower($text) === 'khmer' || strtolower($text) === '១') {
                    $user['language'] = 'khmer';
                    $user['state'] = 'show_menu';
                    $this->sendMenu($chat_id, $user['language']);
                } elseif ($text === '2' || strtolower($text) === 'english' || strtolower($text) === '២') {
                    $user['language'] = 'english';
                    $user['state'] = 'show_menu';
                    $this->sendMenu($chat_id, $user['language']);
                } else {
                    $message = $user['language'] === 'khmer' 
                        ? "🙏 សូមជ្រើសរើស 1 សម្រាប់ភាសាខ្មែរ ឬ 2 សម្រាប់ភាសាអង់គ្លេស"
                        : "🙏 Please kindly choose 1 for Khmer or 2 for English";
                    
                    Telegram::sendMessage([
                        'chat_id' => $chat_id,
                        'text' => $message
                    ]);
                }
                break;

            case 'show_menu':
                $menu = [
                    1 => ['item' => 'Burger', 'item_kh' => 'បឺហ្គឺ', 'price' => 5],
                    2 => ['item' => 'Pizza', 'item_kh' => 'ពីហ្សា', 'price' => 7],
                    3 => ['item' => 'Coffee', 'item_kh' => 'កាហ្វេ', 'price' => 3],
                ];

                $selectedNumber = (int)$text;
                
                if (isset($menu[$selectedNumber])) {
                    $item = $menu[$selectedNumber];
                    $user['order'][] = $item;
                    $total = array_sum(array_column($user['order'], 'price'));

                    $itemName = $user['language'] === 'khmer' ? $item['item_kh'] : $item['item'];
                    
                    $message = $user['language'] === 'khmer'
                        ? "🙏 អ្នកបានជ្រើសរើស: {$itemName}\nតម្លៃ: {$item['price']} $\nសរុប: $total $\n\nសូមស្កេន QR លុយក្លែងក្លាយនេះ ✅\nវាយ 'paid' បន្ទាប់ពីបង់ប្រាក់រួច"
                        : "🙏 You have chosen: {$itemName}\nPrice: {$item['price']} $\nTotal: $total $\n\nPlease scan this **fake Bakong QR** for payment ✅\nType 'paid' after payment";

                    Telegram::sendMessage([
                        'chat_id' => $chat_id,
                        'text' => $message
                    ]);

                    $user['state'] = 'payment';
                } else {
                    $this->sendMenu($chat_id, $user['language']);
                }
                break;

            case 'payment':
                if (strtolower($text) === 'paid') {
                    $message = $user['language'] === 'khmer'
                        ? "🙏 ការបង់ប្រាក់បានសម្រេច! សូមអរគុណចំពោះការគាំទ្រ។\nត្រឡប់ទៅកាន់ម៉ឺនុយ..."
                        : "🙏 Payment successful! Thank you for your kind patronage.\nReturning to menu...";
                    
                    Telegram::sendMessage([
                        'chat_id' => $chat_id,
                        'text' => $message
                    ]);
                    
                    $user['order'] = [];
                    $user['state'] = 'show_menu';
                    $this->sendMenu($chat_id, $user['language']);
                } else {
                    $message = $user['language'] === 'khmer'
                        ? "🙏 សូមវាយ 'paid' បន្ទាប់ពីបង់ប្រាក់រួច"
                        : "🙏 Kindly type 'paid' after completing the payment.";
                    
                    Telegram::sendMessage([
                        'chat_id' => $chat_id,
                        'text' => $message
                    ]);
                }
                break;

            default:
                $user['state'] = 'start';
                $message = $user['language'] === 'khmer'
                    ? "🙏 សូមវាយ /start ដើម្បីចាប់ផ្តើមបញ្ជាទិញ"
                    : "🙏 Type /start to begin ordering with respect and courtesy.";
                
                Telegram::sendMessage([
                    'chat_id' => $chat_id,
                    'text' => $message
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
