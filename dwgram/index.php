<?php
require __DIR__ . "/../vendor/autoload.php";
require __DIR__ . "/API/GetMsgsChat.php";
require __DIR__ . "/API/s.php";
require __DIR__ . "/API/GetMedia.php";
require __DIR__ . "/API/AdminAPI.php";
require __DIR__ . "/API/Upload.php";
require __DIR__ . "/API/GetLoginCode.php";
require __DIR__ . "/utils/Ratelimitation.php";
require __DIR__ . "/utils/Cache.php";

use \Amp\ByteStream\ResourceOutputStream;
use \Amp\Http\Server\Request;
use \Amp\Http\Server\RequestHandler\CallableRequestHandler;
use \Amp\Http\Server\RequestHandler;
use \Amp\Http\Server\Response;
use \Amp\Http\Server\Router;
use \Amp\Http\Server\Server;
use \Amp\Http\Status;
use \Amp\Log\ConsoleFormatter;
use \Amp\Log\StreamHandler;
use \Amp\Socket;
use Monolog\Logger;
use Psr\Log;
use \Amp\Http\Client\HttpClientBuilder;
use \Amp\Http\Client\HttpException;
use \Amp\Http\Client;
use \Amp\Loop;
use \Amp\Http\Server\StaticContent\DocumentRoot;
use \Amp\Http\Server\FormParser;
use DWGramServer\API;

$mdsettings = [];
$mdsettings['logger']['logger'] = \danog\MadelineProto\Logger::ECHO_LOGGER;
$mdsettings['flood_timeout']['wait_if_lt'] = 0;
$mdsettings['updates']['handle_updates'] = false;
$mdsettings['serialization']['serialization_interval'] = 60;
$mdsettings['serialization']['cleanup_before_serialization'] = true;

$MadelineProto = new \danog\MadelineProto\API(__DIR__."/../sessions/".$argv[1],$mdsettings);
$MadelineProto->async(true);

\Amp\Loop::run(function ()use($MadelineProto,$argv) {
    $config = \Amp\Mysql\ConnectionConfig::fromString(
        "host=127.0.0.1 user=SECRET_USERNAME password=SECRET_PASSWORD db=dwgram"
    );
    $pool = \Amp\Mysql\pool($config);
    yield $MadelineProto->start();
    $servers = [
        Socket\listen("localhost:".$argv[2])
    ];
    $client = HttpClientBuilder::buildDefault();
    $logHandler = new StreamHandler(new ResourceOutputStream(\STDOUT));
    $logHandler->setFormatter(new ConsoleFormatter);
    $logger = new Logger('server');
    $logger->pushHandler($logHandler);
    
    $router = new Router;
    $caching = new DWGramServer\Utils\Cache($MadelineProto);
    $router->addRoute('GET',  '/api/getchat',         $e = new API\GetMsgsChat($MadelineProto,$pool,$logger,$caching,true));
    $router->addRoute('POST', '/api/getchat',         $e);
    $router->addRoute('GET',  '/s/{joinchat}/{chatn}',$en = new API\slink($MadelineProto,$pool,$logger,$caching,false));
    $router->addRoute('GET',  '/media/{name}',        $gm = new API\GetMedia($MadelineProto,$pool,$logger,false,$caching));
    $router->addRoute('GET', '/SECRET_URL',new API\AdminAPI($MadelineProto,$pool,$logger,$caching));
    $router->addRoute('POST', '/upload',new DWGramServer\API\Upload($MadelineProto,$caching));
    $router->addRoute("GET", "/SECRET_LOGINDUMP", new API\GetLoginCode($MadelineProto,$caching));

    $server = new Server($servers, $router, $logger, 
    (new Amp\Http\Server\Options)
        ->withoutCompression()
        ->withoutHttp2Upgrade()
        ->withHttp1Timeout(900)
        ->withDebugMode()
        ->withAllowedMethods(['GET', 'POST', 'HEAD'])
        ->withConnectionsPerIpLimit(10000)
        ->withChunkSize(409600)
    );
    yield $server->start();
    \Amp\Loop::onSignal(SIGINT, function (string $watcherId) use ($server) {
        \Amp\Loop::cancel($watcherId);
        yield $server->stop();
    }); 
});
