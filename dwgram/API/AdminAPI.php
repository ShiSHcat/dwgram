<?php

namespace DWGramServer\API;

use Amp;
use \Amp\Http\Server\Request;
use \Amp\Http\Server\Response;
use \Amp\Http\Status;
use \Amp\Promise;
use danog;
use function \Amp\call;

class AdminAPI implements \Amp\Http\Server\RequestHandler
{
    private danog\MadelineProto\API $MadelineProto;
    private $pool;
    private $logger;
    private $caching;

    public function __construct(danog\MadelineProto\API &$MadelineProto, &$pool, &$logger, &$caching)
    {
        $this->MadelineProto = $MadelineProto;
        $this->pool = $pool;
        $this->logger = $logger;
        $this->caching = $caching;
    }

    public function handleRequest(Request $request): \Amp\Promise
    {
        $callable = function (Request $request) {
            \parse_str($request->getUri()->getQuery(), $pquery);
            if (!isset($pquery["sprivtoken1"])||$pquery["sprivtoken1"]!=="SECRET_TOKEN") {
                return new Response(Status::OK, ['content-type' => 'application/json',"Access-Control-Allow-Origin" => "*"], \json_encode([]));
            }
            if ($pquery["action"]=="reboot") {
                die();
            } elseif ($pquery["action"]=="cron") {
                try {
                    echo "resetted\n";
                    yield $this->caching->reset();
                    yield $this->MadelineProto->account->updateStatus(['offline' => false]);
                    $dialogs = yield $this->MadelineProto->getDialogs();
                    foreach ($dialogs as $vvv) {
                        if ($vvv["_"]=="peerChannel") {
                            try {
                                yield $this->MadelineProto->channels->leaveChannel(['channel' => $vvv]);
                            } catch (\Throwable $rs) {
                            }
                        }
                    }
                } catch (\Throwable $xx) {
                    $this->MadelineProto->logger($xx);
                }
                eval("noSuchFunction();");
            } elseif ($pquery["action"]=="0m") {
                yield $this->MadelineProto->account->updateStatus(['offline' => false]);
                return new Response(Status::OK, ['content-type' => 'text/plain',"Access-Control-Allow-Origin" => "*"], "OK");
            }
            return new Response(Status::OK, [
                    "content-type" => "text/plain; charset=utf-8"
                ], \var_export($request->getHeaders(), true));
            return new Response(Status::OK, ['content-type' => 'text/plain',"Access-Control-Allow-Origin" => "*"], "");
        };
        return call($callable, $request);
    }
}
