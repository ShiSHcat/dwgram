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

class GetMsgsChat implements \Amp\Http\Server\RequestHandler
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
            try {
                $body = yield $request->getBody()->buffer();
                if (!empty($body)) {
                    $pquery = \json_decode($body, true);
                    if ($pquery==null) {
                        \parse_str($body, $pquery);
                    }
                } else {
                    \parse_str($request->getUri()->getQuery(), $pquery);
                }
                $siprivate= $pquery["s"]??false;
                $username = $pquery["username"]??false;
                $joinchat = $pquery["joinchat"]??false;
                $dwpagesparse = $pquery["dwpagesparse"]??false;
                $chartn = false;
                if (!(((bool) $username)||((bool) $joinchat))) {
                    return new Response(400, ['content-type' => 'application/json',"Access-Control-Allow-Origin" => "*"], \json_encode([
                        "ok"=>false,
                        "code"=>1,
                        "message"=>"Neither username nor joinchat fragment passed in."
                    ]));
                }

                try {
                    $offset = (int) ($pquery["offset"]??0);
                    $skip = (int) ($pquery["skip"]??0);
                } catch (\Throwable $eeeee) {
                    $offset = 0;
                }
                if ($joinchat&&$this->startsWith("@", $joinchat)) {
                    $username=\str_replace("@", "", $joinchat);
                    $joinchat=false;
                }
                if ($username??false) {
                    try {
                        $un = yield $this->MadelineProto->account->checkUsername(['username' =>"$username"]);
                        if ($un) {
                            throw new \Exception("a");
                        }
                    } catch (\Throwable $xae) {
                        try {
                            if ($this->startsWith("FLOOD_WAIT", $xae->rpc)) {
                                return yield \DWGramServer\Utils\Ratelimitation::handle($xae->rpc);
                            }
                            $this->logger->info($xae->rpc);
                            throw $es;
                        } catch (\Throwable $trt) {
                            return new Response(
                                400,
                                ['content-type' => 'application/json',"Access-Control-Allow-Origin" => "*"],
                                \json_encode([
                                    "ok"=>false,
                                    "errorcode"=>30,
                                    "message"=>"Invalid chat username"
                                ])
                            );
                        }
                    }
                    $_chat = yield $this->MadelineProto->getFullInfo("@$username");
                    if(($_chat["Chat"]["restriction_reason"]??($_chat["restriction_reason"]??false))) {
                        return new Response(400, ['content-type' => 'application/json',"Access-Control-Allow-Origin" => "*"], json_encode([
                            "error"=>44,
                            "description"=>"This channel is restricted.",
                            "restriction_reason"=>($_chat["Chat"]["restriction_reason"]??($_chat["restriction_reason"]??"unknown"))
                        ]));
                    }
                    $e = yield $this->MadelineProto->messages->getHistory([
                        'peer' => "@$username",
                        'offset_id' =>  $offset,
                        'offset_date' => -2147483648,
                        'add_offset' => $skip,
                        'limit' => 100,
                        'max_id' => 2147483647,
                        'min_id' => -2147483648
                    ]);
                } else {
                    $resp = yield $this->caching->get($joinchat);
                    if ($resp == "unsupportedgrouptype") {
                        return new Response(
                            400,
                            ['content-type' => 'application/json',"Access-Control-Allow-Origin" => "*"],
                            \json_encode([
                                "ok"=>false,
                                "errorcode"=>7,
                                "message"=>"Invalid invite, must be a supergroup or channel"
                            ])
                        );
                    } elseif (\is_string($resp)&&$this->startsWith("FLOOD_WAIT", $resp)) {
                        return yield \DWGramServer\Utils\Ratelimitation::handle($resp);
                    } elseif ($resp == "wronghash") {
                        return new Response(
                            400,
                            ['content-type' => 'application/json',"Access-Control-Allow-Origin" => "*"],
                            \json_encode([
                                "ok"=>false,
                                "errorcode"=>9,
                                "message"=>"Invalid chat invite, this userbot could be banned from there."
                            ])
                        );
                    }

                    $e = yield $this->MadelineProto->messages->getHistory([
                        'peer' => $resp,
                        'offset_id' =>  $offset,
                        'offset_date' => -2147483648,
                        'add_offset' => $skip,
                        'limit' => 100,
                        'max_id' => 2147483647,
                        'min_id' => -2147483648
                    ]);
                }
                $msgs = yield $this->parseTResponse($e["messages"], false, $siprivate, $dwpagesparse, $username, $joinchat);
                return new Response(
                    Status::OK,
                    ['content-type' => 'application/json',"Access-Control-Allow-Origin" => "*"],
                    \json_encode([
                        "ok"=>true,
                        "messages_count"=>count($msgs),
                        "messages"=>$msgs,
                    ])
                );
            } catch (\Throwable $x) {
                try {
                    if ($this->startsWith("FLOOD_WAIT", $x->rpc)) {
                        return yield \DWGramServer\Utils\Ratelimitation::handle($x->rpc);
                    }
                    $this->logger->info($x->rpc);
                    throw $es;
                } catch (\Throwable $trt) {
                    $this->logger->info("A getMsgs task failed.");
                    $this->MadelineProto->logger($x);
                    return new Response(500, ['content-type' => 'application/json',"Access-Control-Allow-Origin" => "*"], \json_encode([
                        "ok"=>false,
                        "code"=>8,
                        "message"=>"Task failed."
                    ],JSON_PRETTY_PRINT));
                }
            }
        };
        return call($callable, $request);
    }
    public function parseTResponse($messages, $isprivate, $siprivate, $fastparse, $username, $joinchat)
    {
        $callable = function ($messages, $isprivate, $siprivate, $fastparse, $username, $joinchat) {
            if (\count($messages) < 1) {
                return [];
            }
            $res2 = [];

            /** @var \Amp\Mysql\ResultSet $result */
            if ($username) {
                $statement_ = yield $this->pool->prepare("SELECT * FROM access_data WHERE username = :username");
                $result_ = yield $statement_->execute(['username' => $username]);
            } else {
                $statement_ = yield $this->pool->prepare("SELECT * FROM access_data WHERE jhash = :jhash");
                $result_ = yield $statement_->execute(['jhash' => $joinchat]);
            }
            $alreadyAA=[];
            while (yield $result_->advance()) {
                $alreadyAATemp=$result_->getCurrent();
                $alreadyAA[$alreadyAATemp["msgid"]]=$alreadyAATemp;
            }
            foreach ($messages as $message) {
                if ($message["_"]=="messageService") {
                    continue;
                }
                if ($message["_"]=="messageEmpty") {
                    continue;
                }
                if (($message["media"]??false)) {
                    $mmedia = $message["media"];

                    $type1 = \array_shift($mmedia);
                    $k2e = \array_shift($mmedia)??[];
                    if ((!$k2e)||(!\is_array($k2e))||$this->startsWith("page", $k2e["_"])||$this->startsWith("webPage", $k2e["_"])||$this->startsWith("text", $k2e["_"])||\in_array($k2e["_"], ["messageMediaDice"])) {
                        if ($fastparse) {
                            continue;
                        }
                        $res2[] = [
                            "message"=>($message["message"]??""),
                            "s_url"=>"SECRET_SITEURL/s/".($joinchat?$joinchat:"@".$username)."/".$message["id"],
                            "has_media"=>false,
                            "id"=>$message["id"]
                        ];
                        continue;
                    }
                    $id = \bin2hex(\random_bytes(5));
                    try {
                        $fs = [
                            "dwid"=> $id,
                            "msgid"=>$message["id"],
                            "id_chat"=>\json_encode($message["peer_id"]),
                            "username"=>$username?$username:"",
                            "jhash"=>$joinchat?$joinchat:"",
                            "filename"=>$joinchat
                        ];
                    } catch (\Throwable $ifsds) {
                        if ($fastparse) {
                            continue;
                        }
                        $res2[] = [
                            "message"=>($message["message"]??""),
                            "s_url"=>"SECRET_SITEURL/s/".($joinchat?$joinchat:"@".$username)."/".$message["id"],
                            "has_media"=>false,
                            "id"=>$message["id"]
                        ];
                        continue;
                    }
                    if (isset($ede)) {
                        unset($ede);
                    }
                    foreach ($k2e['attributes']??[] as $attr) {
                        if ($attr['_'] === 'documentAttributeFilename') {
                            $ede = \explode(".", $attr['file_name']);
                        }
                    }
                    if (!isset($ede)) {
                        $ede = [""];
                    }
                    if ($siprivate) {
                        if ($fastparse) {
                            $res2[(string) \implode(".", $ede)?\str_replace("--", "/", \implode(".", $ede)):$message["id"]] = "SECRET_SITEURL/s/".($fs["jhash"]??($fs["username"]))."/".$fs["msgid"];
                            continue;
                        }
                        $res2[] = [
                            "message"=>($message["message"]??""),
                            "s_url"=>"SECRET_SITEURL/s/".($joinchat?$joinchat:"@".$username)."/".$message["id"],
                            "sm_url"=>"SECRET_SITEURL/s/".($joinchat?$joinchat:"@".$username)."/".$message["id"]."?text=true",
                            "has_media"=>true,
                            "fname"=>\implode(".", $ede),
                            "has_media"=>true,
                            "vlink"=>$message["id"],
                            "type"=>$k2e["_"],
                            "id"=>$message["id"]
                        ];
                    } else {
                        if ($alreadyAA[$message["id"]]??false) {
                            $id=$alreadyAA[$message["id"]]["dwid"];
                        } else {
                            $statement = yield $this->pool->prepare("INSERT INTO `access_data` (`dwid`, `id_chat`, `msgid`, `username`, `jhash`) VALUES (:dwid, :id_chat, :msgid, :username, :jhash)");
                            $result =    yield $statement->execute($fs);
                        }
                        if ($fastparse) {
                            $res2[(string) \implode(".", $ede)?\str_replace("--", "/", \implode(".", $ede)):$message["id"]] = "SECRET_SITEURL/s/".($joinchat?$joinchat:"@".$username)."/".$message["id"];
                            continue;
                        }

                        $res2[] = [
                            "message"=>($message["message"]??""),
                            "has_media"=>true,
                            "mediaURL"=>"SECRET_SITEURL/media/$id".(($ede[\count($ede)-1])?".".($ede[\count($ede)-1]):""),
                            "s_url"=>"SECRET_SITEURL/s/".($joinchat?$joinchat:"@".$username)."/".$message["id"],
                            "sm_url"=>"SECRET_SITEURL/s/".($joinchat?$joinchat:"@".$username)."/".$message["id"]."?text=true",
                            "fname"=>\implode(".", $ede),
                            "vlink"=>$message["id"],
                            "type"=>$k2e["_"],
                            "id"=>$message["id"]
                        ];
                    }
                } else {
                    if ($fastparse) {
                        continue;
                    }
                    $res2[]=[
                        "message"=>$message["message"],
                        "has_media"=>false,
                        "s_url"=>"SECRET_SITEURL/s/".($joinchat?$joinchat:"@".$username)."/".$message["id"],
                        "id"=>$message["id"]
                    ];
                }
            }
            return $res2;
        };
        return call($callable, $messages, $isprivate, $siprivate, $fastparse, $username, $joinchat);
    }
    private function startsWith($startString, $string)
    {
        $len = \strlen($startString);
        return (\substr($string, 0, $len) === $startString);
    }
}
