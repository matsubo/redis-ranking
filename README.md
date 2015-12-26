Ranking API for Redis 
=============================

[![Coverage Status](https://coveralls.io/repos/matsubo/redis-ranking/badge.png?branch=master)](https://coveralls.io/r/matsubo/redis-ranking)
[![Build Status](https://travis-ci.org/matsubo/redis-ranking.png?branch=master)](https://travis-ci.org/matsubo/redis-ranking)
[![Dependencies Status](https://depending.in/matsubo/redis-ranking.png)](http://depending.in/matsubo/redis-ranking)
[![Stable Version](https://poser.pugx.org/redis/ranking/v/stable.png)](https://packagist.org/packages/redis/ranking)
[![Download Count](https://poser.pugx.org/redis/ranking/downloads.png)](https://packagist.org/packages/redis/ranking)


[![endorse](https://api.coderwall.com/matsubo/endorsecount.png)](https://coderwall.com/matsubo)
  
Abstracting Redis's `Sorted Set` APIs to use as a real-time ranking system.

Requirements
-----------------------------
- Redis
  - >=2.4
- PhpRedis extension
  - https://github.com/nicolasff/phpredis
- PHP
  - >=5.3, >=5.4, >=5.5 >=5.6, >=7.0
- Composer



Installation
----------------------------

* Using composer

```
{
    "require": {
       "redis/ranking": "1.0.*"
    }
}
```

```
$ php composer.phar update redis/ranking --dev
```

Benchmark sample
-----------------------------
```
% php sample/benchmark.php
Add: 18,350 queries/s
Update: 17,876 queries/s
Get score: 21,361 queries/s
Get rank: 22,123 queries/s
php sample/benchmark.php  1.50s user 2.96s system 43% cpu 10.203 total
```


How to run unit test
----------------------------

Run with default setting.
```
% vendor/bin/phpunit -c phpunit.xml.dist
```

Currently tested with PHP 5.3.15 + Redis 2.6.12.


TODO
-----------------------------
- Implement following APIs
  - `zRemRangeByRank`, zDeleteRangeByRank - Remove all members in a sorted set within the given indexes
  - `zRemRangeByScore`, zDeleteRangeByScore - Remove all members in a sorted set within the given scores
- Implement `zUnion` API as a static helper API.

History
----------------------------
- Next Release
  - Refactoring
  - Support PHP 7.0
  - Library update
- 1.0.1
  - bugfix: correct variable name.
  - supports travis CI and passed test.
- 1.0.0
  - Published



License
----------------------------
It is released under the [PHP License, version 3.01](http://www.php.net/license/3_01.txt).

Copyright
-----------------------------
- Yuki Matsukura
  - http://matsu.teraren.com/blog/




[![Bitdeli Badge](https://d2weczhvl823v0.cloudfront.net/matsubo/redis-ranking/trend.png)](https://bitdeli.com/free "Bitdeli Badge")

