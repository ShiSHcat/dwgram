<?php
namespace DWGramServer\LoadBalancer\API;

use \Amp\Http\Server\RequestHandler;
use \Amp\Http\Server\Response;
use function \Amp\call;

class GetLoginCode implements RequestHandler
{
    protected $client;
    protected $servers;
    public function __construct($servers, &$dwgrams)
    {
        $this->servers = $servers;
        $this->client = $dwgrams;
    }
    public function handleRequest(\Amp\Http\Server\Request $request): \Amp\Promise
    {
        $callable = function (\Amp\Http\Server\Request $request) {
            $zzresp2 = $request->getUri()->getQuery();
            return new Response(200, ["access-control-allow-origin"=>"*","content-type"=>"application/json"], \json_encode(yield $this->client->GET_every("/SECRET_LOGINDUMP", true)));
        };
        return call($callable, $request);
    }
    public function reset()
    {
        return call(function () {});
    }
    private function startsWith($startString, $string)
    {
        $len = \strlen($startString);
        return (\substr($string, 0, $len) === $startString);
    }
    public function MinuteReset()
    {
        return call(function () {});
    }
}
