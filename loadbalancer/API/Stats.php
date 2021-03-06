<?php
namespace DWGramServer\LoadBalancer\API;

use \Amp\Http\Server\RequestHandler;
use \Amp\Http\Server\Response;
use function \Amp\call;

class Stats implements RequestHandler
{
    protected $statslogger;
    protected $sql;
    public function __construct(&$statslogger, &$sql)
    {
        $this->statslogger = $statslogger;
        $this->sql = $sql;
    }
    public function handleRequest(\Amp\Http\Server\Request $request): \Amp\Promise
    {
        $callable = function (\Amp\Http\Server\Request $request) {
            $zc = yield $this->sql->query('SELECT COUNT(*) FROM `access_data`');
            $cza = 0;
            while (yield $zc->advance()) {
                $row = $zc->getCurrent();
                $cza = $row["COUNT(*)"];
            }
            return new Response(200, ["access-control-allow-origin"=>"*","content-type"=>"application/json"], \json_encode([
                "API_online"=>true,
                "your_ip"=>$request->getHeader("cf-connecting-ip"),
                "media_dwgrammed_counter"=>$cza,
                "visitor_number"=>$this->statslogger->getVisits(),
                "last_reboot_API_server"=>\round($_SERVER["REQUEST_TIME_FLOAT"]) * 1000
            ], JSON_PRETTY_PRINT));
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
