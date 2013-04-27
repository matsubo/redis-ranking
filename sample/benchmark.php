<?php
require __DIR__ . '/../vendor/autoload.php';

use Matsubo\Redis\Ranking;


$redis = new \Redis();
$redis->connect('127.0.0.1', 6379);

$key = 'benchmark';

$ranking = new Ranking($key, $redis);

$users = 50000;


// add
$bench = new Ubench;
$bench->start();
for ($i = 0; $i < $users; $i++ ) {
    $result = $ranking->setUserScore($i, -mt_rand(1, 10000));
}
$bench->end();
echo sprintf("Add: %s qps\n" , number_format(round($users / ($bench->getTime(true) / 1000))));



// update(overwrite)
$bench = new Ubench;
$bench->start();
for ($i = 0; $i < $users; $i++ ) {
    $result = $ranking->setUserScore($i, -mt_rand(1, 10000));
}
$bench->end();
echo sprintf("Update: %s qps\n" , number_format(round($users / ($bench->getTime(true) / 1000))));


// get score
$bench = new Ubench;
$bench->start();
for ($i = 0; $i < $users; $i++ ) {
    $ranking->getScore($i);
}
$bench->end();
echo sprintf("Get score: %s qps\n" , number_format(round($users / ($bench->getTime(true) / 1000))));


// get rank
$bench = new Ubench;
$bench->start();
for ($i = 0; $i < $users; $i++ ) {
    $ranking->getRank($i);
}
$bench->end();
echo sprintf("Get rank: %s qps\n" , number_format(round($users / ($bench->getTime(true) / 1000))));








// show top 10
// print_r($ranking->getRange(0, 10, true));

// cleanup
$redis->del($key);



