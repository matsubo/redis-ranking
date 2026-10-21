<?php

declare(strict_types=1);

// php sample/usecase.php  (needs Redis on REDIS_HOST, default 127.0.0.1)

use Matsubo\Redis\ExpireMode;
use Matsubo\Redis\Ranking;

require __DIR__ . '/../vendor/autoload.php';

$redis = new Redis();
$redis->connect(getenv('REDIS_HOST') ?: '127.0.0.1', 6379);

$ranking = new Ranking('usecase', $redis);

// Add scores
$ranking->setUserScore('kanako', 1);
$ranking->setUserScore('ayaka', 50);
$ranking->setUserScore('shiori', 100);
$ranking->incrementScore('kanako', 10);    // 11.0

var_dump($ranking->getScore('ayaka'));     // float(50)
var_dump($ranking->getScore('nobody'));    // NULL

// Ranks are 0-based
var_dump($ranking->getRank('shiori'));     // int(2)  from the lowest score
var_dump($ranking->getRevRank('shiori'));  // int(0)  from the highest score

// Top 2
var_dump($ranking->getRevRangeWithScores(0, 1));   // ['shiori' => 100.0, 'ayaka' => 50.0]

// Users scoring 10 or more, and how many there are
var_dump($ranking->getRangeByScores(10, '+inf'));  // ['kanako', 'ayaka', 'shiori']
var_dump($ranking->countUsersByScores(10, '+inf')); // int(3)

// A daily board: expire in 24 hours unless an expiry is already set
$ranking->expire(24 * 60 * 60 * 1000, ExpireMode::IfNone);

// Cleanup
var_dump($ranking->deleteAllUser());       // ['kanako', 'ayaka', 'shiori']
