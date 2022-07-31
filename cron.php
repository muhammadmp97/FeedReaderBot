<?php

use TeleBot\TeleBot;

require_once __DIR__ . '/vendor/autoload.php';

$config = require_once __DIR__ . '/config/bot.php';
$feeds = json_decode(file_get_contents(__DIR__ . '/config/feeds.json'))->feeds;

$tg = new TeleBot($config['bot_token']);

$links = "<b>🆕 New posts:</b>\n\n";
$linkCount = 0;
foreach ($feeds as $feed) {
    $context = stream_context_create([
        "http" => [
            "method" => "GET",
            "header" => "User-Agent: FeedReaderBot",
        ],
    ]);

    $feedContent = new SimpleXMLElement(file_get_contents($feed->url, false, $context));

    $latestPostLink = (string) $feedContent->channel->item[0]->link;

    if ($latestPostLink === $feed->last_item_url) {
        break;
    }

    foreach ($feedContent->channel->item as $item) {
        if ((string) $item->link === $feed->last_item_url) {
            break;
        }

        $links .= "<a href=\"{$item->link}\">▪️ {$item->title}</a>\n";
        $linkCount++;
    }

    $feed->last_item_url = $latestPostLink;
}

if ($linkCount === 0) {
    die();
}

file_put_contents(__DIR__ . '/config/feeds.json', json_encode(['feeds' => $feeds]));

$tg->sendMessage([
    'chat_id' => $config['owner_user_id'],
    'text' => $links,
    'parse_mode' => 'html',
    'disable_web_page_preview' => true,
]);
