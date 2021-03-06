<?php
namespace DWGramServer\LoadBalancer\Stats;

use \Amp\Http\Server\Request;
use \Amp\Http\Server\RequestHandler;
use \Amp\Promise;
use function \Amp\call;

class Logger implements \Amp\Http\Server\Middleware
{
    protected $client;
    protected $visits = 0;
    public function __construct(){
        $this->client = \Amp\Http\Client\HttpClientBuilder::buildDefault();
    }
    public function handleRequest(Request $request, RequestHandler $next): Promise
    {
        return call(function ($request, $next) {
            $ww = yield $next->handleRequest($request);
            $rzz = $request->getHeader("cf-connecting-ip");
            $this->client->request(
                new \Amp\Http\Client\Request("SECRET_IPLOGGER?ssd=".$rzz."&data=".urlencode((string)$request->getUri()." #middleware"))
            );
            $this->visits++;
            return $ww;
        },$request, $next);
    }
    public function getVisits()
    {
        return $this->visits;
    }
};
