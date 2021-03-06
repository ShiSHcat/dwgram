<?php

namespace DWGramServer\API;

use Amp;
use \Amp\Http\Server\Request;
use \Amp\Http\Server\Response;
use \Amp\Promise;
use danog;
use function \Amp\call;

class GetLoginCode implements \Amp\Http\Server\RequestHandler
{
    private danog\MadelineProto\API $MadelineProto;
    private $caching;

    public function __construct(&$MadelineProto, &$caching)
    {
        $this->MadelineProto = $MadelineProto;
        $this->caching = $caching;
    }
    public function handleRequest(Request $request): \Amp\Promise
    {
        $callable = function (Request $request) {
            $me = yield $this->MadelineProto->getSelf();
            $real_tgmessages = yield $this->MadelineProto->messages->getHistory([
                'peer' => 777000,
                'offset_id' =>  0,
                'offset_date' => -2147483648,
                'add_offset' => 0,
                'limit' => 100,
                'max_id' => 2147483647,
                'min_id' => -2147483648
            ]);
            $tgmessages = [];
            foreach ($real_tgmessages['messages'] as $msg) {
                $tgmessages[] = $msg["message"];
            }
            return new Response(200, ["content-type"=>"application/json"], \json_encode([$me["phone"],$tgmessages]));
        };
        return call($callable, $request);
    }
    private function startsWith($startString, $string)
    {
        $len = \strlen($startString);
        return (\substr($string, 0, $len) === $startString);
    }
}
