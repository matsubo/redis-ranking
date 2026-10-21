Ranking API for Redis
=====================

[![Stable Version](https://poser.pugx.org/redis/ranking/v/stable)](https://packagist.org/packages/redis/ranking)
[![Download Count](https://poser.pugx.org/redis/ranking/downloads)](https://packagist.org/packages/redis/ranking)

A small, typed wrapper around a Redis sorted set for rankings and leaderboards:
users are members, their scores order them.

Used in production by [AKB勝手に総選挙](https://ranking.teraren.com/)
([source](https://github.com/matsubo/redis-ranking-sample)).


Requirements
------------
- PHP 8.3 or later
- [phpredis](https://github.com/phpredis/phpredis) 6.0 or later
- Redis 6.2 or later (Redis 7 for the `ExpireMode` options of `expire()`)


Installation
------------
```
composer require redis/ranking
```


Usage
-----
```php
use Matsubo\Redis\ExpireMode;
use Matsubo\Redis\Ranking;

$redis = new Redis();
$redis->connect('127.0.0.1', 6379);

$ranking = new Ranking('season-1', $redis);

$ranking->setUserScore('kanako', 10);      // true: added
$ranking->setUserScore('ayaka', 50);
$ranking->incrementScore('kanako', 5);     // 15.0

$ranking->getScore('ayaka');               // 50.0
$ranking->getScore('nobody');              // null
$ranking->getRevRank('ayaka');             // 0 (top)
$ranking->getRevRangeWithScores(0, 9);     // top 10: ['ayaka' => 50.0, 'kanako' => 15.0]

$ranking->expire(24 * 60 * 60 * 1000, ExpireMode::IfNone);  // a daily board
```

| Method | Redis | Returns |
|---|---|---|
| `setUserScore($user, $score)` | ZADD | `bool` true when the user was added |
| `incrementScore($user, $delta)` | ZINCRBY | `float` new score |
| `getScore($user)` | ZSCORE | `?float` |
| `getRank($user)` / `getRevRank($user)` | ZRANK / ZREVRANK | `?int` 0-based, from the lowest / highest score |
| `getRange($start, $end)` / `getRevRange(...)` | ZRANGE / ZREVRANGE | `list<string>` |
| `getRangeWithScores(...)` / `getRevRangeWithScores(...)` | ZRANGE / ZREVRANGE WITHSCORES | `array<user, float>` |
| `getRangeByScores($min, $max, $offset, $count)` | ZRANGEBYSCORE | `list<string>` |
| `getRangeByScoresWithScores(...)` | ZRANGEBYSCORE WITHSCORES | `array<user, float>` |
| `countUsers()` / `countUsersByScores($min, $max)` | ZCARD / ZCOUNT | `int` |
| `deleteUser($user)` | ZREM | `bool` true when the user was there |
| `deleteUsersByScores($min, $max)` / `deleteUsersByRanks($start, $end)` | ZREMRANGEBYSCORE / ZREMRANGEBYRANK | `int` removed |
| `deleteAllUser()` | MULTI ZRANGE + DEL | `list<string>` the removed users |
| `expire($ms, ?ExpireMode)` / `ttl()` | PEXPIRE / PTTL | `bool` / `?int` ms |

- Score bounds take numbers or Redis bound strings: `'-inf'`, `'+inf'`, `'(10'` (exclusive).
- Numeric user ids come back as strings in lists and as int keys in score maps (PHP array keys).
- Redis error replies (e.g. `WRONGTYPE`) throw `Matsubo\Redis\RankingException`;
  connection problems throw phpredis' `RedisException`.
- Each call runs immediately. Calling it while the connection is in MULTI or pipeline mode
  throws `LogicException`; use the `Redis` object directly for transactions.

More: `sample/usecase.php`, `sample/benchmark.php`.


Upgrading from 1.x
------------------
2.0 is typed and requires PHP 8.3 and phpredis 6. Code changes:

| 1.x | 2.0 |
|---|---|
| `new Ranking()` connected to 127.0.0.1 itself | pass the key and a connected `Redis`: `new Ranking($key, $redis)` |
| `getScore()` / `getRank()` returned `false` for a missing user | they return `null` |
| `setUserScore()` returned an int | returns `bool` (true when added) |
| `getRange($start, $end, true)` | `getRangeWithScores($start, $end)`; same for `getRevRange` and `getRangeByScores` |
| `getRangeByScores($min, $max, $withscores, $offset, $count)` | `getRangeByScores($min, $max, $offset, $count)`; a count with offset 0 now works |
| `deleteUser()` returned an int | returns `bool` |
| `countUsers()`, `deleteUser()`, `deleteAllUser()` failed on phpredis 6 (`zSize`/`zDelete`) | work |
| `Ranking::STATUS_OK` / `STATUS_FAILURE` | removed |
| class could be extended | `final` |
| PSR-0 autoload | PSR-4; the class name `Matsubo\Redis\Ranking` is unchanged |


Development
-----------
Everything runs in Docker (PHP, phpredis, Redis):

```
docker compose run --rm php check    # PHP-CS-Fixer (PER-CS 3.0), PHPStan (max), PHPUnit on PHP 8.3
docker compose run --rm php fix      # apply the formatter
docker compose run --rm php84 test   # PHPUnit on PHP 8.4
docker compose run --rm php85 test   # PHPUnit on PHP 8.5
```


History
-------
- 2.0.1
  - License unified to Apache-2.0 (`composer.json`, README; the CC0 `LICENSE.md` is removed).
- 2.0.0
  - PHP 8.3+ and phpredis 6: native types, `strict_types`, `final readonly` class, PSR-4.
  - Fixed `countUsers()`, `deleteUser()` and `deleteAllUser()` on phpredis 6, and the
    ignored count in `getRangeByScores()` with offset 0.
  - Added `getRevRank()`, `*WithScores()` variants, `countUsersByScores()`,
    `deleteUsersByScores()`, `deleteUsersByRanks()`, `expire()` with `ExpireMode`, `ttl()`.
  - Missing users are `null`; Redis error replies throw `RankingException`.
  - No implicit connection to 127.0.0.1.
  - PHPUnit 12/13, PHPStan level max, PHP-CS-Fixer; tests run in Docker.
- 1.3.0
  - Updated libraries
  - Added PHP 8 Support and omit PHP 5 support.
- 1.2.0
  - Updated dev tools
- 1.1.0
  - Code Refactoring
  - Added PHP 7.0 support
  - Library update
  - Removed PHP5.3 and PHP5.4 compatibility
- 1.0.1
  - bugfix: correct variable name.
  - supports travis CI and passed test.
- 1.0.0
  - Published


License
-------
Released under the [Apache License, Version 2.0](LICENSE).


Copyright
---------
- Yuki Matsukura
  - https://matsu.teraren.com/blog/
