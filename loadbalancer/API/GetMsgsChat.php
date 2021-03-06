<?php
namespace DWGramServer\LoadBalancer\API;

use \Amp\Http\Server\RequestHandler;
use \Amp\Http\Server\Response;
use function \Amp\call;

class GetMsgsChat implements RequestHandler
{
    protected $client;
    protected $reqCache = [];
    protected $channelManagedBy = [];
    protected $ips = [];
    protected $servers;
    public function __construct($servers, &$dwgrams)
    {
        $this->servers = $servers;
        $this->client = $dwgrams;
    }
    public function handleRequest(\Amp\Http\Server\Request $request): \Amp\Promise
    {
        $callable = function (\Amp\Http\Server\Request $request) {
            
            $body = yield $request->getBody()->buffer();
            if (!empty($body)) {
                $pquery = \json_decode($body, true);
                if ($pquery==null) {
                    \parse_str($body, $pquery);
                }
            } else {
                \parse_str($request->getUri()->getQuery(), $pquery);
            }
            \ksort($pquery);
            
            if ($pquery["username"]??false) {
                $pquery["joinchat"] = "@".$pquery["username"];
            }
            if($this->startsWith("@",$pquery["joinchat"])){
                $this->client->log($pquery["joinchat"]." #getchatdwgram",$request->getHeader("cf-connecting-ip"));
            } else {
                $this->client->log("https://t.me/joinchat/".$pquery["joinchat"]." #getchatdwgram",$request->getHeader("cf-connecting-ip"));
            }
            if (!($pquery["joinchat"]??false)) {
                return new Response(400, ["access-control-allow-origin"=>"*","content-type"=>"application/json"], '{"ok":false,"code":1,"message":"Neither username nor joinchat fragment passed in."}');
            }
            if (\strlen($pquery["joinchat"])<6||\substr_count($pquery["joinchat"], "@")>1) {
                return new Response(400, ["access-control-allow-origin"=>"*","content-type"=>"application/json"], '{"ok":false,"code":20,"message":"Username/joinhash is either too short or invalid"}');
            }
            if(isset($pquery["skip"])&&$pquery["skip"] == 0) unset($pquery["skip"]);
            unset($pquery["username"]);
            $whitelist = ["joinchat","skip","offset","s","dwpagesparse"];
            $static = ["s","dwpagesparse"];
            $pqueryclone = $pquery;
            foreach ($pqueryclone as $k=>$v) {
                if (!\in_array($k, $whitelist)) {
                    unset($pqueryclone[$k]);
                } elseif (\in_array($k, $static)) {
                    $pqueryclone[$k] = "true";
                }
            }
            $json = \json_encode($pqueryclone);
            $json_ = \json_encode($pquery);
            unset($pqueryclone);
            $servedby = yield $this->client->getSharedJHCache($pquery["joinchat"]);
            $userip = $request->getHeader("cf-connecting-ip");
            $vdfe = $this->ips[$userip]??0;
            $excedded = false;
            if ((!$userip)||!isset($this->ips[$userip])) {
                $this->ips[$userip] = \time();
            } elseif (0.5>((\time()-$this->ips[$userip])/60)) {
                $excedded = true;
            } else {
                $this->ips[$userip] = \time();
            }
            if(isset($pquery["SECRET_RATELIMIT_TOKEN"])) $excedded = false;
            if (($excedded||!isset($pquery["__cachebypass"]))&&isset($this->reqCache[$json])) {
                return new Response($this->reqCache[$json][1], ["access-control-allow-origin"=>"*","content-type"=>"application/json"], $this->reqCache[$json][0]);
            } elseif (isset($pquery["__cachebypass"])) {
                $dresp = yield $this->client->POST("/api/getchat", $json_, $servedby);
                $servresp_ = $dresp;
                $servresp = $dresp->getBody();
            } else {
                $dresp = yield $this->client->POST("/api/getchat", $json_, $servedby);
                $servresp_ = $dresp;
                $servresp = $dresp->getBody();
            }
            $rz = yield $servresp->buffer();
            $rzz = \json_decode($rz, true)["messages"]??[];
            foreach ($rzz as $k=>$v) {
                if (isset($v["mediaURL"])) {
                    yield $this->client->registerMediaCache($v["mediaURL"], $servedby);
                }
            }
            $this->reqCache[$json][0] = $rz;
            $this->reqCache[$json][1] = $servresp_->getStatus();
            return new Response($servresp_->getStatus(), ["access-control-allow-origin"=>"*","content-type"=>"application/json"], $rz);
        };
        return call($callable, $request);
    }
    public function MinuteReset()
    {
        $callable = function () {
            $this->reqCache = [];
        };
        return call($callable);
    }
    public function reset()
    {
        $callable = function () {
            $this->reqCache = [];
            $this->channelManagedBy = [];
        };
        return call($callable);
    }
    private function startsWith($startString, $string)
    {
        $len = \strlen($startString);
        return (\substr($string, 0, $len) === $startString);
    }
}
