<?php

declare(strict_types=1);

// php sample/benchmark.php  (needs Redis on REDIS_HOST, default 127.0.0.1)

use Matsubo\Redis\Ranking;

require __DIR__ . '/../vendor/autoload.php';

$redis = new Redis();
$redis->connect(getenv('REDIS_HOST') ?: '127.0.0.1', 6379);

$ranking = new Ranking('benchmark', $redis);
$users = 50_000;

/** @param Closure(int): mixed $operation */
$bench = static function (string $label, Closure $operation) use ($users): void {
    $start = hrtime(true);
    for ($i = 0; $i < $users; ++$i) {
        $operation($i);
    }
    $seconds = (hrtime(true) - $start) / 1e9;
    printf("%-10s %s queries/s\n", $label . ':', number_format($users / $seconds));
};

$bench('Add', static fn(int $i): bool => $ranking->setUserScore($i, -mt_rand(1, 10_000)));
$bench('Update', static fn(int $i): bool => $ranking->setUserScore($i, -mt_rand(1, 10_000)));
$bench('Get score', static fn(int $i): ?float => $ranking->getScore($i));
$bench('Get rank', static fn(int $i): ?int => $ranking->getRank($i));

$ranking->deleteAllUser();
