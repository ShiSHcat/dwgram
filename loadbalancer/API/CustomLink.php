<?php
namespace DWGramServer\LoadBalancer\API;

use \Amp\Http\Server\RequestHandler;
use \Amp\Http\Server\Response;
use \Amp\Http\Server\Router;
use function \Amp\call;

class CustomLink implements RequestHandler
{
    protected $client;
    protected $servers;
    public function __construct($servers, $dwgrams)
    {
        $this->servers = $servers;
        $this->client = $dwgrams;
        $this->pscache = [];
    }
    public function handleRequest(\Amp\Http\Server\Request $request): \Amp\Promise
    {
        $callable = function (\Amp\Http\Server\Request $request) {
            $zpq = $request->getUri()->getQuery();
            $pquery = $request->getAttribute(Router::class);
            $chatn = \urlencode($pquery["chatn"]);
            $joinchat = $pquery["joinchat"];
            if($this->startsWith("@",$pquery["joinchat"])) $this->client->log($pquery["joinchat"]." #viewLink",$request->getHeader("cf-connecting-ip"));
            else $this->client->log("https://t.me/joinchat/".$pquery["joinchat"]." #viewLink",$request->getHeader("cf-connecting-ip"));

            $dresp = [yield $this->client->GET("/s/$joinchat/$chatn?$zpq", yield $this->client->getSharedJHCache($pquery["joinchat"]),$request->getHeader("range")),yield $this->client->getSharedJHCache($pquery["joinchat"])];
            $servedby = $dresp[1];
            $servresp = $dresp[0]->getBody();
            $h = $dresp[0]->getHeaders();
            $headers = ["accept-ranges"=>"bytes","access-control-allow-origin"=>"*","content-type"=>$h["content-type"][0]];
            if (isset($h["content-disposition"][0])) {
                $headers["content-disposition"] = $h["content-disposition"][0];
            }
            $resa = new Response($dresp[0]->getStatus(), $headers);
            $resa->setBody($servresp);
            if (isset($h["content-length"][0])) {
                $resa->setHeader("content-length", $h["content-length"][0]);
            }
            if (isset($h["content-range"][0])) {
                $resa->setHeader("content-range", $h["content-range"][0]);
            }
            return $resa;
        };
        return call($callable, $request);
    }
    public function reset()
    {
        $callable = function () {
            yield $this->channelManagedBy = [];
        };
        return call($callable);
    }
    public function MinuteReset()
    {
        return call(function () {});
    }
    private function startsWith($startString, $string)
    {
        $len = \strlen($startString);
        return (\substr($string, 0, $len) === $startString);
    }
}
