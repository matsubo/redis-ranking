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

echo sprintf("Add: %s queries/s\n" , number_format(round($users / ($bench->getTime(true)))));



// update(overwrite)
$bench = new Ubench;
$bench->start();
for ($i = 0; $i < $users; $i++ ) {
    $result = $ranking->setUserScore($i, -mt_rand(1, 10000));
}
$bench->end();
echo sprintf("Update: %s queries/s\n" , number_format(round($users / ($bench->getTime(true)))));


// get score
$bench = new Ubench;
$bench->start();
for ($i = 0; $i < $users; $i++ ) {
    $ranking->getScore($i);
}
$bench->end();
echo sprintf("Get score: %s queries/s\n" , number_format(round($users / ($bench->getTime(true)))));


// get rank
$bench = new Ubench;
$bench->start();
for ($i = 0; $i < $users; $i++ ) {
    $ranking->getRank($i);
}
$bench->end();
echo sprintf("Get rank: %s queries/s\n" , number_format(round($users / ($bench->getTime(true)))));








// show top 10
// print_r($ranking->getRange(0, 10, true));

// cleanup
$redis->del($key);



