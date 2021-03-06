<?php

namespace DWGramServer\API;

use Amp;
use \Amp\Http\Server\Request;
use \Amp\Http\Server\Response;
use \Amp\Http\Server\Router;
use \Amp\Promise;
use danog;
use function \Amp\call;

class getMedia implements \Amp\Http\Server\RequestHandler
{
    private danog\MadelineProto\API $MadelineProto;
    private $pool;
    private $logger;
    private $ips = [];
    private $_media= false;
    private $is_api= false;
    private $eh;
    private $error;
    private $cache;

    public function __construct(danog\MadelineProto\API &$MadelineProto, &$pool, &$logger, $_media, &$cache)
    {
        $this->MadelineProto = $MadelineProto;
        $this->pool = $pool;
        $this->logger = $logger;
        $this->_media = $_media;
        $this->eh = new \Amp\Http\Server\DefaultErrorHandler();
        $this->error = "404";
        $this->cache = $cache;
    }
    public function handleRequest(Request $request): \Amp\Promise
    {
        $callable = function (Request $request) {
            $arg222 = \rtrim(\trim($request->getAttribute(Router::class)["name"], "."), ".");

            if (\strpos($arg222, '.') !== false) {
                $arg = \explode(".", $arg222);
                \array_pop($arg);
                $arg = \implode(".", $arg);
            } else {
                $arg = $arg222;
            }
            $statement_ = yield $this->pool->prepare("SELECT * FROM access_data WHERE dwid = :id");
            $result_ = yield $statement_->execute(["id"=>$arg]);
            while (yield $result_->advance()) {
                $tfilep=$result_->getCurrent();
                $rr = yield $this->recreateMediaObject($tfilep, $this->cache, $this->MadelineProto);
                if (\is_string($rr)) {
                    return new Response(404, ['content-type' => 'application/json',"Access-Control-Allow-Origin" => "*"], \json_encode([
                        "error"=>"Message was deleted",
                        "0_debug"=>$rr,
                    ]));
                }
                if (($rr["_"]??"messageEmpty")=="messageEmpty") {
                    return new Response(404, ['content-type' => 'application/json',"Access-Control-Allow-Origin" => "*"], \json_encode([
                        "error"=>"Message was deleted"
                    ]));
                }
                $resp = yield $this->MadelineProto->downloadToResponse($rr, $request);
                $resd = ($rr)["media"];
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
            }
            if ($pquery["json"]??false) {
                return new Response(404, ['content-type' => 'application/json',"Access-Control-Allow-Origin" => "*"], \json_encode([
                        "ok"=>false,
                        "code"=>404,
                        "error"=>"Link not found"
                    ]));
            }
            return yield $this->eh->handleError(404, "Link not found, or there was an error when getting it.", $request);
        };
        return call($callable, $request);
    }
    public static function recreateMediaObject($object, $cache, $MadelineProto)
    {
        $callable = function ($object, $cache, $MadelineProto) {
            if ($object["username"]??"" !== "") {
                try {
                    return (yield $MadelineProto->channels->getMessages(['channel' => "@".$object["username"], 'id' => [$object["msgid"]]]))["messages"][0];
                } catch (\Throwable $fg) {
                    try {
                        if (\strpos($fg->rpc??"", "FLOOD_WAIT")!== false) {
                            return yield \DWGramServer\Utils\Ratelimitation::handle($fg->rpc);
                        }
                        throw $fg;
                    } catch (\Throwable $trt) {
                        \var_dump($trt);
                        return "invalid";
                    }
                }
            } else {
                $joinchat = yield $cache->get($object["jhash"]);
            }
            if (\is_string($joinchat)) {
                return "invalid";
            }
            try {
                return (yield $MadelineProto->channels->getMessages(['channel' => $joinchat, 'id' => [$object["msgid"]]]))["messages"][0];
            } catch (\Throwable $fg) {
                try {
                    if (\strpos($fg->rpc??"", "FLOOD_WAIT")!== false) {
                        return yield \DWGramServer\Utils\Ratelimitation::handle($fg->rpc);
                    }
                    throw $fg;
                } catch (\Throwable $trt) {
                    return "invalid";
                }
            }
        };
        return call($callable, $object, $cache, $MadelineProto);
    }
}
