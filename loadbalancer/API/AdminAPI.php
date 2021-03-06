<?php
namespace DWGramServer\LoadBalancer\API;

use \Amp\Http\Server\Request;
use \Amp\Http\Server\Response;
use \Amp\Http\Status;
use \Amp\Promise;
use function \Amp\call;

class AdminAPI implements \Amp\Http\Server\RequestHandler
{
    private $objs;


    public function __construct($objects)
    {
        $this->objs = $objects;
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
                foreach ($this->objs as $v) {
                    $v->MinuteReset();
                }
                return new Response(Status::OK, ['content-type' => 'text/plain',"Access-Control-Allow-Origin" => "*"], "OK");
            } elseif ($pquery["action"]=="midnight") {
                foreach ($this->objs as $v) {
                    $v->reset();
                }
                return new Response(Status::OK, ['content-type' => 'text/plain',"Access-Control-Allow-Origin" => "*"], "OK");
            }
            //testing  time
            return new Response(Status::OK, [
                    "content-type" => "text/plain; charset=utf-8"
                ], \var_export($request->getHeaders(), true));
            return new Response(Status::OK, ['content-type' => 'text/plain',"Access-Control-Allow-Origin" => "*"], "noop");
        };
        return call($callable, $request);
    }
}
