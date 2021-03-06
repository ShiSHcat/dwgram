<?php
namespace DWGramServer\LoadBalancer\API;

use \Amp\Http\Server\RequestHandler;
use \Amp\Http\Server\Response;
use \Amp\Http\Server\Router;
use function \Amp\call;

class Media implements RequestHandler
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
            $pquery = $request->getAttribute(Router::class);
            $mediaURL = $pquery["name"];
            $acc = yield $this->client->getMediaCache("SECRET_SITEURL/media/".$mediaURL);
            $this->client->log($mediaURL." #viewLink",$request->getHeader("cf-connecting-ip"));
            $dresp = yield $this->client->GET("/media/$mediaURL", $acc, $request->getHeader("range"));
            $servresp = $dresp->getBody();
            $h = $dresp->getHeaders();
            $headers = ["accept-ranges"=>"bytes","access-control-allow-origin"=>"*","content-type"=>$h["content-type"][0]];
            if (isset($h["content-disposition"][0])) {
                $headers["content-disposition"] = $h["content-disposition"][0];
            }
            if (isset($h["content-range"][0])) {
                $headers["content-range"] = $h["content-range"][0];
            }
            $respon = new Response($dresp->getStatus(), $headers);
            $respon->setBody($servresp);
            if (isset($h["content-length"][0])) {
                $respon->setHeader("content-length", $h["content-length"][0]);
            }
            return $respon;
        };
        return call($callable, $request);
    }
    private function startsWith($startString, $string)
    {
        $len = \strlen($startString);
        return (\substr($string, 0, $len) === $startString);
    }
    public function reset()
    {
        return call(function () {});
    }
    public function MinuteReset()
    {
        return call(function () {});
    }
}
