<?php
namespace DWGramServer\LoadBalancer\utils;

use \Amp\Loop;

class GarbageCollector
{
    public static bool $lock = false;
    public static int $checkIntervalMs = 2000;
    public static int $memoryDiffMb = 1;
    private static int $memoryConsumption = 0;

    public static function start(): void
    {
        if (static::$lock) {
            return;
        }
        static::$lock = true;

        Loop::repeat(static::$checkIntervalMs, static function () {
            $currentMemory = static::getMemoryConsumption();
            if ($currentMemory > static::$memoryConsumption + static::$memoryDiffMb) {
                gc_collect_cycles();
            }
        });
    }

    private static function getMemoryConsumption(): int
    {
        $memory = \round(\memory_get_usage()/1024/1024, 1);
        return (int) $memory;
    }
}