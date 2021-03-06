<?php

namespace DWGramServer\Utils;

use function \Amp\call;

final class Cache
{
    private $cache = [];
    private $orderedJoinhashes = [];
    private $MadelineProto;
    public function __construct($madeline)
    {
        $this->MadelineProto = $madeline;
        #yield $this->cache[""]="wronghash";
    }
    private function register($join, $ChatInvite)
    {
        $callable = function ($join, $ChatInvite) {
            if ($ChatInvite == "wronghash") {
                $this->cache[$join] = "wronghash";
                return "wronghash";
            }
            $this->cache[$join] = $ChatInvite["chat"];
            return $ChatInvite["chat"];
        };
        return call($callable, $join, $ChatInvite);
    }
    private function delete($join)
    {
        $callable = function ($join) {
            unset($this->cache[$join]);
            return true;
        };
        return call($callable, $join);
    }
    public function reset()
    {
        $callable = function () {
            $this->cache = [];
        };
        return call($callable);
    }
    public function getAll()
    {
        $callable = function () {
            return $this->cache;
        };
        return call($callable);
    }
    public function get($join)
    {
        $callable = function ($join) {
            $cache = $this->cache[$join]??false;
            try {
                if ($cache) {
                    if ($cache =="wronghash") {
                        return "wronghash";
                    }
                    yield $this->MadelineProto->channels->getChannels(['id' => [$cache["id"]], ]);
                    return $cache;
                }
                throw new \Exception("nocache");
            } catch (\Throwable $xxe) {
                if ($xxe->getMessage() !== "nocache") {
                    yield $this->delete($join);
                }
                $this->MadelineProto->logger($xxe);
                try {
                    $ChatInvite = yield $this->MadelineProto->messages->checkChatInvite(['hash' => 'https://t.me/joinchat/'.$join]);
                    if ($ChatInvite["_"]=="chatInviteAlready") {
                        $e2=yield $this->register($join, $ChatInvite);
                        return $e2;
                    }
                    if (!(($ChatInvite["broadcast"]??false))) {
                        return "unsupportedgrouptype";
                    }
                    try {
                        $chat_ = yield $this->MadelineProto->messages->importChatInvite(['hash' => 'https://t.me/joinchat/'.$join])["chats"][0];
                        $this->orderedJoinhashes[] = $chat_id = $this->MadelineProto->getID($chat_);
                        $_chat = yield $this->MadelineProto->getFullInfo($chat_id);
                        if($_chat["Chat"]["restricted"]??false){
                            return "unsupportedgrouptype";
                        }
                    } catch (\Throwable $xer) {
                        $this->MadelineProto->logger($xer);
                        if (\strpos($fg->rpc??"", "FLOOD_WAIT")!== false) {
                            return $fg->rpc;
                        }
                        try {
                            if (!empty($this->orderedJoinhashes)) {
                                try {
                                    yield $this->MadelineProto->channels->leaveChannel(['channel' => $this->orderedJoinhashes[\array_pop($this->orderedJoinhashes)]]);
                                } catch (\Throwable $w) {
                                }
                            } else {
                                $dialogs = yield $this->MadelineProto->getDialogs();
                                foreach ($dialogs as $vvv) {
                                    if ($vvv["_"]=="peerChannel") {
                                        try {
                                            yield $this->MadelineProto->channels->leaveChannel(['channel' => $vvv]);
                                        } catch (\Throwable $rs) {
                                        }
                                    }
                                }
                            }
                            $chat_ = yield $this->MadelineProto->messages->importChatInvite(['hash' => 'https://t.me/joinchat/'.$join])["chats"][0];
                        } catch (\Throwable $ers) {
                            yield $this->register($join, "wronghash");
                            $this->MadelineProto->logger($ers);
                            return "wronghash";
                        }
                    }

                    $e2=yield $this->register($join, ["chat"=>$this->MadelineProto->getID($chat_)]);
                    return $e2;
                } catch (\Throwable $fg) {
                    try {
                        $this->MadelineProto->logger($fg);
                        if (\strpos($fg->rpc??"", "FLOOD_WAIT")!== false) {
                            return $fg->rpc;
                        }
                        throw $fg;
                    } catch (\Throwable $trt) {
                        yield $this->register($join, "wronghash");
                        $this->MadelineProto->logger($trt);
                        return "wronghash";
                    }
                }
            }
        };
        return call($callable, $join);
    }
    private function startsWith($startString, $string)
    {
        $len = \strlen($startString);
        return (\substr($string, 0, $len) === $startString);
    }
}

