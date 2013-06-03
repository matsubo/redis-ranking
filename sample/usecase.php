<?php
require __DIR__ . '/../vendor/autoload.php';

use Matsubo\Redis\Ranking;

$redis = new \Redis();
$redis->connect('127.0.0.1', 6379);

$ranking = new Ranking($key = 'usecase', $redis);

// Add score
$ranking->setUserScore('kanako', 1);
$ranking->setUserScore('ayaka', 50);
$ranking->setUserScore('shiori', 100);

// get score
var_dump($ranking->getScore('kanako'));   // float(1)
var_dump($ranking->getScore('ayaka'));    // float(50)
var_dump($ranking->getScore('shiori'));   // float(100)

// get rank
var_dump($ranking->getRank('kanako'));  // int(0)
var_dump($ranking->getRank('ayaka'));   // int(1)
var_dump($ranking->getRank('shiori'));  // int(2)

/*
 * Show top 2
array(3) {
  ["kanako"]=>
  string(1) "1"
  ["ayaka"]=>
  string(2) "50"
 */
var_dump($ranking->getRange(0, 1, true));

// Cleanup
$redis->del($key);
