<?php

namespace DWGramServer\Utils;

use \Amp\Http\Server\Response;
use function \Amp\call;

class Ratelimitation
{
    public function __construct($obj)
    {
    }
    public static function handle($obj)
    {
        $callable = function ($obj) {
            return new Response(429, ["dwinte"=>true,"content-type"=>"text/plain"], $obj);
        };
        return call($callable, $obj);
    }
}
