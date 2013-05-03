Ranking API for Redis 
=============================



Abstract
-----------------------------
Abstracting Redis's `Sorted Set` APIs to use as a real-time ranking system.

* ![project status](http://stillmaintained.com/matsubo/redis-ranking.png) 
* [![Build Status](https://travis-ci.org/matsubo/redis-ranking.png?branch=master)](https://travis-ci.org/matsubo/redis-ranking)
* Code coverage
  * 100%

Requirements
-----------------------------
- Redis
  - >=2.4
- PhpRedis
  - https://github.com/matsubo/phpredis
- PHP
  - >=5.3
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

Benchmark sample
-----------------------------
```
% time php sample/benchmark.php
Add: 20,029,757 qps
Update: 18,569,067 qps
Get: 21,912,140 qps
php sample/benchmark.php  1.16s user 2.18s system 44% cpu 7.551 total
```


How to run unit test
----------------------------
```
% cp phpunit.xml.dist  phpunit.xml
% vendor/bin/phpunit
```
or

Run with default.
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
- Associate with travis.
- throws exception if return value from Redis class invalid.


History
----------------------------
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
  - http://matsu.teraren.com/


