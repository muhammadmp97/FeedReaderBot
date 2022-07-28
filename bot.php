<?php

use TeleBot\TeleBot;

require_once __DIR__ . '/vendor/autoload.php';

$config = require_once __DIR__ . '/config/bot.php';
$feeds = json_decode(file_get_contents(__DIR__ . '/config/feeds.json'))->feeds;

$tg = new TeleBot($config['bot_token']);

try {
    $tg->listen('/add %p', function ($url) use ($tg, $config, $feeds) {
        foreach ($feeds as $feed) {
            if ($url === $feed->url) {
                return $tg->sendMessage([
                    'chat_id' => $config['owner_user_id'],
                    'reply_to_message_id' => $tg->message->message_id,
                    'text' => '❗️ Feed exists!',
                ]);
            }
        }

        $feeds[] = [
            'url' => $url,
            'reader' => 'dev.to',
        ];

        file_put_contents(__DIR__ . '/config/feeds.json', json_encode(['feeds' => $feeds]));

        $tg->sendMessage([
            'chat_id' => $config['owner_user_id'],
            'reply_to_message_id' => $tg->message->message_id,
            'text' => '✅ Feed added successfully!',
        ]);
    });

    $tg->listen('/remove %p', function ($url) use ($tg, $config, $feeds) {
        $newFeeds = array_filter($feeds, function ($feed) use ($url) {
            return $feed->url !== $url;
        });

        if (count($newFeeds) === count($feeds)) {
            return $tg->sendMessage([
                'chat_id' => $config['owner_user_id'],
                'reply_to_message_id' => $tg->message->message_id,
                'text' => '❗️ Feed not found!',
            ]);
        }

        file_put_contents(__DIR__ . '/config/feeds.json', json_encode(['feeds' => $newFeeds]));

        $tg->sendMessage([
            'chat_id' => $config['owner_user_id'],
            'reply_to_message_id' => $tg->message->message_id,
            'text' => '✅ Feed removed successfully!',
        ]);
    });
} catch (Throwable $th) {
    $tg->sendMessage([
        'chat_id' => $config['owner_user_id'],
        'text' => $th->getMessage(),
    ]);
}
