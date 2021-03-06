<?php

if (!file_exists('madeline.php')) {
    copy('https://phar.madelineproto.xyz/madeline.php', 'madeline.php');
}
include 'madeline.php';
$settings = [];
$settings['app_info']['api_id'] = "SECRET_YOURAPIID";
$settings['app_info']['api_hash'] = "SECRET_YOURAPIHASH";
$MadelineProto = new \danog\MadelineProto\API($argv[1].'.madeline',$settings);
$MadelineProto->async(true);

$MadelineProto->loop(function () use ($MadelineProto) {
    yield $MadelineProto->start();
    yield $MadelineProto->messages->sendMessage(['peer' => '@SECRET_YOURTELEGRAMUSERNAME', 'message' => "Hello!"]);
});
