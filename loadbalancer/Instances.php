<?php

namespace DWGramServer\LoadBalancer;

use \Amp\Http\Client\HttpClientBuilder;
use \Amp\Http\Client\Request;
use function \Amp\call;

class Instances
{
    protected $servers = [];
    protected $client;
    protected $blisted = [];
    protected $sharedjc = [];
    protected $mediacache = [];
    protected $bslittt = 99999999999999;
    public function DEBUG_MEMUSE()
    {
    }
    public function __construct($servers)
    {
        $this->client = HttpClientBuilder::buildDefault();
        $this->servers = $servers;
    }
    public function log($data,$ip)
    {
        $this->client->request(
            //EXAMPLE OF IPLOGGER HERE: [repo root]/iplogger_example.php (PHP FPM)
            new \Amp\Http\Client\Request("SECRET_IPLOGGER?ssd=".$ip."&data=".urlencode($data))
        );
    }
    public function getRandom()
    {
        $callable = function () {
            do {
                $nz = \array_rand($this->servers);
            } while (\in_array($nz, $this->blisted));
            return $this->servers[$nz];
        };
        return call($callable);
    }
    public function GET($endpoint, $session, $range=false)
    {
        $callable = function ($endpoint, $session, $range) {
            $respa = new Request("http://$session$endpoint");
            $respa->setBodySizeLimit($this->bslittt);
            $respa->setInactivityTimeout(0);
            $respa->setProtocolVersions(["1.0","1.1"]);
            $respa->setTransferTimeout($this->bslittt); // 120 seconds
            if($range) $respa->setHeader("Range",$range);
            $iz = yield $this->client->request($respa);
            while ($iz->getStatus() == 429) {
                yield $this->blacklist($session);
                $session = yield $this->getRandom();
                $respa = new Request("http://$session$endpoint");
                $respa->setBodySizeLimit($this->bslittt);
                $respa->setProtocolVersions(["1.0","1.1"]);
                $respa->setInactivityTimeout(0);
                if($range) $respa->setHeader($range);
                $respa->setTransferTimeout($this->bslittt); // 120 seconds
                $iz = yield $this->client->request($respa);
            }
            return $iz;
        };
        return call($callable, $endpoint, $session, $range);
    }
    public function POST($endpoint, $body, $session)
    {
        $callable = function ($endpoint, $body, $session) {
            $respa = new Request("http://$session$endpoint");
            $respa->setBodySizeLimit($this->bslittt);
            $respa->setBody($body);
            $respa->setInactivityTimeout(0);
            $respa->setProtocolVersions(["1.0","1.1"]);
            $respa->setTransferTimeout($this->bslittt); // 120 seconds
            $iz = yield $this->client->request($respa);
            while ($iz->getStatus() == 429) {
                yield $this->blacklist($session);
                $session = yield $this->getRandom();
                $respa = new Request("http://$session$endpoint");
                $respa->setBodySizeLimit($this->bslittt);
                $respa->setBody($body);
                $respa->setInactivityTimeout(0);
                $respa->setProtocolVersions(["1.0","1.1"]);
                $respa->setTransferTimeout($this->bslittt); // 120 seconds
                $iz = yield $this->client->request($respa);
            }
            return $iz;
        };
        return call($callable, $endpoint, $body, $session);
    }
    public function randomGET($endpoint)
    {
        $callable = function ($endpoint) {
            $rrandom = yield $this->getRandom();
            $rrz = yield $this->GET($endpoint, $rrandom);
            return [$rrz,$rrandom];
        };
        return call($callable, $endpoint);
    }
    public function randomPOST($endpoint, $body)
    {
        $callable = function ($endpoint, $body) {
            $rrandom = yield $this->getRandom();
            $rrz = yield $this->POST($endpoint, $body, $rrandom);
            return [$rrz,$rrandom];
        };
        return call($callable, $endpoint, $body);
    }
    public function blacklist($endpoint)
    {
        $callable = function ($endpoint) {
            $this->blisted[] = $endpoint;
            yield $this->banSharedMediaCache($endpoint);
            yield $this->banSharedJHCache($endpoint);
        };
        return call($callable, $endpoint);
    }
    public function unblacklist($endpoint)
    {
        $callable = function ($endpoint) {
            unset($this->blisted[\array_search($endpoint, $this->blisted)]);
        };
        return call($callable, $endpoint);
    }
    public function getMediaCache($url)
    {
        $callable = function ($url) {
            $z = $this->mediacache[$url]??false;
            if ($z) {
                return $z;
            }

            $zrando = yield $this->getRandom();
            yield $this->registerMediaCache($url, $zrando);
            return $zrando;
        };
        return call($callable, $url);
    }
    public function registerMediaCache($url, $account)
    {
        $callable = function ($url, $account) {
            if (!isset($this->mediacache[$url])) {
                $this->mediacache[$url] = $account;
            }
        };
        return call($callable, $url, $account);
    }
    public function banSharedMediaCache($account)
    {
        $callable = function ($account) {
            foreach (\array_keys($this->sharedjc, $account) as $media) {
                unset($this->mediacache[$media]);
            }
        };
        return call($callable, $account);
    }
    public function registerSharedJHCache($jh, $account)
    {
        $callable = function ($jh, $account) {
            if (!isset($this->sharedjc[$jh])) {
                $this->sharedjc[$jh] = $account;
            }
        };
        return call($callable, $jh, $account);
    }
    public function banSharedJHCache($account)
    {
        $callable = function ($account) {
            foreach (\array_keys($this->sharedjc, $account) as $jhashs) {
                unset($this->sharedjc[$jhashs]);
            }
        };
        return call($callable, $account);
    }
    public function getSharedJHCache($jh)
    {
        $callable = function ($jh) {
            $z = $this->sharedjc[$jh]??false;
            if ($z) {
                return $z;
            }

            $zrando = yield $this->getRandom();
            yield $this->registerSharedJHCache($jh, $zrando);
            return $zrando;
            ;
        };
        return call($callable, $jh);
    }
    public function reset()
    {
        $callable = function () {
            foreach ($this->servers as $v) {
                yield $this->GET("/SECRET_URL?action=cron&sprivtoken1=SECRET_TOKEN", $v);
            }
            $this->blisted = [];
            $this->mediacache = [];
        };
        return call($callable);
    }
    public function MinuteReset()
    {
        return call(function () {
            $this->blisted = [];
            foreach ($this->servers as $v) {
                yield $this->GET("/SECRET_URL?action=0m&sprivtoken1=SECRET_TOKEN", $v);
            }
        });
    }
    public function GET_every($url, $isjson=false)
    {
        return call(function ($url, $isjson) {
            $this->blisted = [];
            $ubcs = [];
            foreach ($this->servers as $v) {
                $resp = yield $this->GET($url, $v);
                $resp = $resp->getBody();
                $resp = yield $resp->buffer();
                $ubcs[] = $isjson?\json_decode($resp):$resp;
            }
            return $ubcs;
        }, $url, $isjson);
    }
    private function startsWith($startString, $string)
    {
        $len = \strlen($startString);
        return (\substr($string, 0, $len) === $startString);
    }
}
