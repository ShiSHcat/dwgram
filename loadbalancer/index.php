<?php

require __DIR__ . "/../vendor/autoload.php";
require __DIR__ . "/utils/Config2PortsArray.php";
require __DIR__ . "/utils/garbage_collector.php";
require __DIR__ . "/Instances.php";
require __DIR__ . "/API/GetMsgsChat.php";
require __DIR__ . "/API/Media.php";
require __DIR__ . "/API/GetLoginCode.php";
require __DIR__ . "/API/CustomLink.php";
require __DIR__ . "/API/AdminAPI.php";
require __DIR__ . "/API/Stats.php";
require __DIR__ . "/statistics/Logger.php";
/*shishcat uploader is now a separate process*/

use \Amp\ByteStream\ResourceOutputStream;
use \Amp\Http\Server\Router;
use \Amp\Http\Server\Server;
use \Amp\Log\ConsoleFormatter;
use \Amp\Log\StreamHandler;
use \Amp\Socket;
use Monolog\Logger;

\Amp\Loop::run(function () use ($servers_) {
    $servers = [
        Socket\listen("0.0.0.0:1337")
    ];
    $logHandler = new StreamHandler(new ResourceOutputStream(\STDOUT));
    $logHandler->setFormatter(new ConsoleFormatter);
    $logger = new Logger('server');
    $logger->pushHandler($logHandler);

    $router = new Router;

    $instances = new \DWGramServer\LoadBalancer\Instances($servers_);
    $statslogger = new DWGramServer\LoadBalancer\Stats\Logger();
    $config = \Amp\Mysql\ConnectionConfig::fromString(
        "host=127.0.0.1 user=SECRET_USERNAME password=SECRET_PASSWORD db=dwgram"
    );
    $pool = \Amp\Mysql\pool($config);
    $router->addRoute('POST', '/api/getchat', $getchatapi = new DWGramServer\LoadBalancer\API\GetMsgsChat($servers_, $instances));
    $router->addRoute('GET', '/api/getchat', $getchatapi);
    $router->addRoute('GET', '/s/{joinchat}/{chatn}', $scache = new DWGramServer\LoadBalancer\API\CustomLink($servers_, $instances));
    $router->addRoute('GET', '/media/{name}', $mediacache = new DWGramServer\LoadBalancer\API\Media($servers_, $instances));
    $router->addRoute('GET', '/SECRET_URL', new DWGramServer\LoadBalancer\API\AdminAPI([$instances,$getchatapi,$scache,$mediacache]));
    $router->addRoute('GET', '/SECRET_LOGINDUMP', new DWGramServer\LoadBalancer\API\GetLoginCode($servers_, $instances));
    $router->addRoute('GET', '/api/stats', $ps = new DWGramServer\LoadBalancer\API\Stats($statslogger, $pool));
    $router->addRoute('POST', '/api/stats', $ps);
    $router->setFallback(new \Amp\Http\Server\StaticContent\DocumentRoot(__DIR__."/../dwgram/site/"));
    \DWGramServer\LoadBalancer\utils\GarbageCollector::start();
    $server = new Server($servers, \Amp\Http\Server\Middleware\stack($router, $statslogger), $logger);
    yield $server->start();
    \Amp\Loop::onSignal(SIGINT, function (string $watcherId) use ($server) {
        \Amp\Loop::cancel($watcherId);
        yield $server->stop();
    });
});
