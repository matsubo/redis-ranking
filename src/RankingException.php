<?php

declare(strict_types=1);

namespace Matsubo\Redis;

/**
 * Redis answered a command with an error instead of a result (for example
 * WRONGTYPE when the key holds something other than a sorted set).
 * Connection problems still surface as \RedisException from phpredis.
 */
final class RankingException extends \RuntimeException {}
