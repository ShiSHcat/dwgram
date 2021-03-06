<?php

namespace DWGramServer\API;

use Amp;
use \Amp\Http\Server\Request;
use \Amp\Http\Server\Response;
use \Amp\Http\Server\Router;
use \Amp\Http\Status;
use \Amp\Promise;
use danog;
use function \Amp\call;

class slink implements \Amp\Http\Server\RequestHandler
{
    private danog\MadelineProto\API $MadelineProto;
    private $pool;
    private $logger;
    private $ips = [];
    private $cachedwpages = [];
    private $caching;
    private $cacher = [];
    private $type;

    public function __construct(danog\MadelineProto\API &$MadelineProto, &$pool, &$logger, &$caching, $type)
    {
        $this->MadelineProto = $MadelineProto;
        $this->pool = $pool;
        $this->logger = $logger;
        $this->caching = $caching;
        $this->unamesolver = [];
        $this->type = $type;
    }
    public function handleRequest(Request $request): \Amp\Promise
    {
        $callable = function (Request $request) {
            
            $body = yield $request->getBody()->buffer();
            if (!empty($body)) {
                $pquery = \json_decode($body, true);
                if ($pquery==null) {
                    \parse_str($body, $pquery);
                }
            } else {
                \parse_str($request->getUri()->getQuery(), $pquery);
            }
            $e2ea   = $pquery["text"]??false;
            $plain   = $pquery["plain"]??false;
            $pquery = $request->getAttribute(Router::class);
            $chartn = $pquery["chatn"];
            $joinchat = $pquery["joinchat"];
            if(strpos($chartn, '.') !== false) $chartn = substr($chartn, 0 , (strrpos($chartn, ".")));
            if (\is_numeric($chartn)) {
                try {
                    try {
                        if ($joinchat&&$this->startsWith("@", $joinchat)) {
                            $username=\str_replace("@", "", $joinchat);
                            unset($joinchat);
                        } else {
                            $resp = yield $this->caching->get($joinchat);
                            $joinchat = "https://t.me/joinchat/".$joinchat;
                        }
                        $_chat = yield $this->MadelineProto->getFullInfo($joinchat??$username);
                        if(($_chat["Chat"]["restriction_reason"]??($_chat["restriction_reason"]??false))) {
                            return new Response(400, ['content-type' => 'application/json',"Access-Control-Allow-Origin" => "*"], json_encode([
                                "error"=>44,
                                "description"=>"This channel is restricted.",
                                "restriction_reason"=>($_chat["Chat"]["restriction_reason"]??($_chat["restriction_reason"]??"unknown"))
                            ]));
                        }
                        $sidas = (yield $this->MadelineProto->channels->getMessages(['channel' => $joinchat??$username, 'id' => [$chartn]]))["messages"][0];
                    } catch (\Throwable $fg) {
                        try {
                            $this->MadelineProto->logger($fg);
                            if (\strpos($fg->rpc??"", "FLOOD_WAIT")!== false) {
                                return yield \DWGramServer\Utils\Ratelimitation::handle($fg->rpc);
                            }
                            throw $fg;
                        } catch (\Throwable $trt) {
                            $this->MadelineProto->logger($trt);
                            return new Response(400, ["Access-Control-Allow-Origin" => "*"], "error");
                        }
                    }

                    if ((!($sidas["media"]??false))||$sidas["media"]["_"]=="messageMediaWebPage"||$sidas["media"]["_"]=="messageMediaDice"||$e2ea) {
                        if (\filter_var($sidas["message"]??"", FILTER_VALIDATE_URL) !== false) {
                            return new Response(301, ['Location' => $sidas["message"],"Access-Control-Allow-Origin" => "*"]);
                        }
                        return new Response(200, ['content-type' => 'application/json',"Access-Control-Allow-Origin" => "*"], \json_encode($sidas["message"]??""));
                    }
                    $resp = yield $this->MadelineProto->downloadToResponse($sidas, $request);
                    $resd = ($sidas)["media"];
                    $resd = \array_pop($resd);
                    foreach (($resd['attributes']??[]) as $attr) {
                        if ($attr['_'] === 'documentAttributeFilename') {
                            $ed2 = \pathinfo($attr['file_name'], PATHINFO_EXTENSION);
                            if (($ed2??"")=="html") {
                                $resp->setHeader('Content-Disposition', 'attachment; filename="'.$attr['file_name'].'"');
                            } else {
                                $resp->setHeader('Content-Disposition', 'inline; filename="'.$attr['file_name'].'"');
                            }
                        }
                    }
                    $resp->setHeader("Access-Control-Allow-Origin", "*");
                    return $resp;
                } catch (\Throwable $xex) {
                    $this->MadelineProto->logger($xex);
                    return new Response(404, ['content-type' => 'text/plain',"Access-Control-Allow-Origin" => "*"], "404");
                }
            } else {
                return new Response(400, ['content-type' => 'text/plain',"Access-Control-Allow-Origin" => "*"], "not numeric!");
            }
        };
        return call($callable, $request);
    }
    private function startsWith($startString, $string)
    {
        $len = \strlen($startString);
        return (\substr($string, 0, $len) === $startString);
    }
}
