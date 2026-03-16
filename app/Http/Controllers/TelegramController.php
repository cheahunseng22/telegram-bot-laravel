<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Telegram\Bot\Laravel\Facades\Telegram;

class TelegramController extends Controller
{
    public function webhook(Request $request)
    {
        $chat_id = $request['message']['chat']['id'] ?? null;
        $text = $request['message']['text'] ?? '';

        if ($chat_id && $text === '/start') {
            Telegram::sendMessage([
                'chat_id' => $chat_id,
                'text' => 'Welcome to my bot 👋'
            ]);
        }

        return response()->json(['status' => 'ok']);
    }
}
