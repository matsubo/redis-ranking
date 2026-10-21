<?php

declare(strict_types=1);

namespace Matsubo\Redis;

/**
 * A ranking stored in one Redis sorted set: users are members, their scores
 * order them. Ranks are 0-based; getRank()/getRange() count from the lowest
 * score, getRevRank()/getRevRange() from the highest.
 *
 * Every method runs its command right away. Do not call them while the
 * connection is in MULTI or pipeline mode (phpredis then queues the command
 * and returns the Redis object instead of a result); a \LogicException says so.
 *
 * Numeric user ids come back from Redis as strings in lists and as int keys
 * in score maps (PHP turns numeric string keys into ints).
 *
 * @author Yuki Matsukura <matsubokkuri@gmail.com>
 */
final readonly class Ranking
{
    public function __construct(
        private string $key,
        private \Redis $redis,
    ) {}

    /** The sorted set's key. */
    public function key(): string
    {
        return $this->key;
    }

    /**
     * Sets a user's score, adding the user if needed.
     *
     * @return bool true when the user was added, false when an existing score was replaced
     */
    public function setUserScore(string|int $userId, int|float $score): bool
    {
        return $this->int($this->run('ZADD', fn(): mixed => $this->redis->zAdd($this->key, $score, $userId))) === 1;
    }

    /** The user's score, or null when the user is not in the ranking. */
    public function getScore(string|int $userId): ?float
    {
        $score = $this->run('ZSCORE', fn(): mixed => $this->redis->zScore($this->key, $userId), missing: true);

        return $score === null ? null : $this->float($score);
    }

    /** 0-based rank counted from the lowest score, or null when the user is not in the ranking. */
    public function getRank(string|int $userId): ?int
    {
        $rank = $this->run('ZRANK', fn(): mixed => $this->redis->zRank($this->key, $userId), missing: true);

        return $rank === null ? null : $this->int($rank);
    }

    /** 0-based rank counted from the highest score, or null when the user is not in the ranking. */
    public function getRevRank(string|int $userId): ?int
    {
        $rank = $this->run('ZREVRANK', fn(): mixed => $this->redis->zRevRank($this->key, $userId), missing: true);

        return $rank === null ? null : $this->int($rank);
    }

    /**
     * Adds $delta to the user's score (a user that is not in the ranking
     * starts at 0) and returns the new score.
     */
    public function incrementScore(string|int $userId, int|float $delta): float
    {
        return $this->float($this->run('ZINCRBY', fn(): mixed => $this->redis->zIncrBy($this->key, $delta, (string) $userId)));
    }

    /**
     * Users from rank $start to $end inclusive, lowest score first. Negative
     * ranks count from the end (-1 is the last).
     *
     * @return list<string>
     */
    public function getRange(int $start = 0, int $end = -1): array
    {
        return $this->members($this->run('ZRANGE', fn(): mixed => $this->redis->zRange($this->key, $start, $end)));
    }

    /**
     * Like getRange(), with each user's score.
     *
     * @return array<string|int, float> user id => score, lowest score first
     */
    public function getRangeWithScores(int $start = 0, int $end = -1): array
    {
        return $this->scores($this->run('ZRANGE', fn(): mixed => $this->redis->zRange($this->key, $start, $end, ['withscores' => true])));
    }

    /**
     * Users from rank $start to $end inclusive, highest score first.
     *
     * @return list<string>
     */
    public function getRevRange(int $start = 0, int $end = -1): array
    {
        return $this->members($this->run('ZREVRANGE', fn(): mixed => $this->redis->zRevRange($this->key, $start, $end)));
    }

    /**
     * Like getRevRange(), with each user's score.
     *
     * @return array<string|int, float> user id => score, highest score first
     */
    public function getRevRangeWithScores(int $start = 0, int $end = -1): array
    {
        return $this->scores($this->run('ZREVRANGE', fn(): mixed => $this->redis->zRevRange($this->key, $start, $end, ['withscores' => true])));
    }

    /**
     * Users with $min <= score <= $max, lowest score first. Bounds are numbers
     * or Redis bound strings ('-inf', '+inf', '(10' for exclusive).
     *
     * @param int|null $count at most this many users, after skipping $offset
     *
     * @return list<string>
     */
    public function getRangeByScores(int|float|string $min, int|float|string $max, int $offset = 0, ?int $count = null): array
    {
        return $this->members($this->run('ZRANGEBYSCORE', fn(): mixed => $this->redis->zRangeByScore(
            $this->key,
            (string) $min,
            (string) $max,
            self::limit($offset, $count),
        )));
    }

    /**
     * Like getRangeByScores(), with each user's score.
     *
     * @return array<string|int, float> user id => score, lowest score first
     */
    public function getRangeByScoresWithScores(int|float|string $min, int|float|string $max, int $offset = 0, ?int $count = null): array
    {
        return $this->scores($this->run('ZRANGEBYSCORE', fn(): mixed => $this->redis->zRangeByScore(
            $this->key,
            (string) $min,
            (string) $max,
            ['withscores' => true] + self::limit($offset, $count),
        )));
    }

    /** Number of users in the ranking. */
    public function countUsers(): int
    {
        return $this->int($this->run('ZCARD', fn(): mixed => $this->redis->zCard($this->key)));
    }

    /** Number of users with $min <= score <= $max (bounds as in getRangeByScores()). */
    public function countUsersByScores(int|float|string $min, int|float|string $max): int
    {
        return $this->int($this->run('ZCOUNT', fn(): mixed => $this->redis->zCount($this->key, (string) $min, (string) $max)));
    }

    /** @return bool true when the user was in the ranking */
    public function deleteUser(string|int $userId): bool
    {
        return $this->int($this->run('ZREM', fn(): mixed => $this->redis->zRem($this->key, (string) $userId))) === 1;
    }

    /**
     * Removes users with $min <= score <= $max (bounds as in getRangeByScores()).
     *
     * @return int number of users removed
     */
    public function deleteUsersByScores(int|float|string $min, int|float|string $max): int
    {
        return $this->int($this->run('ZREMRANGEBYSCORE', fn(): mixed => $this->redis->zRemRangeByScore($this->key, (string) $min, (string) $max)));
    }

    /**
     * Removes users from rank $start to $end inclusive (lowest score first;
     * negative ranks count from the end).
     *
     * @return int number of users removed
     */
    public function deleteUsersByRanks(int $start, int $end): int
    {
        return $this->int($this->run('ZREMRANGEBYRANK', fn(): mixed => $this->redis->zRemRangeByRank($this->key, $start, $end)));
    }

    /**
     * Removes the whole ranking in one transaction.
     *
     * @return list<string> the users that were in it
     */
    public function deleteAllUser(): array
    {
        $result = $this->run('MULTI', fn(): mixed => $this->redis->multi()->zRange($this->key, 0, -1)->del($this->key)->exec());

        return $this->members(\is_array($result) ? $result[0] ?? null : null);
    }

    /**
     * Lets the whole ranking expire after $milliseconds (PEXPIRE), e.g. for a
     * daily or seasonal board. The mode options need Redis 7.
     *
     * @return bool true when the expiry was set (false: no such key, or $mode's condition was not met)
     */
    public function expire(int $milliseconds, ?ExpireMode $mode = null): bool
    {
        return $this->run('PEXPIRE', fn(): mixed => $this->redis->pExpire($this->key, $milliseconds, $mode?->value), missing: true) === true;
    }

    /** Milliseconds until the ranking expires, or null when it has no expiry or does not exist. */
    public function ttl(): ?int
    {
        $ttl = $this->int($this->run('PTTL', fn(): mixed => $this->redis->pttl($this->key)));

        return $ttl < 0 ? null : $ttl;
    }

    /**
     * Runs one command and turns phpredis' false into an exception, or into
     * null when $missing (false also means "no such member" there).
     *
     * @param \Closure(): mixed $command
     */
    private function run(string $name, \Closure $command, bool $missing = false): mixed
    {
        $this->redis->clearLastError();
        $result = $command();

        if ($result instanceof \Redis) {
            throw new \LogicException('Ranking cannot be used while the Redis connection is in MULTI or pipeline mode');
        }
        if ($result === false) {
            $error = $this->redis->getLastError();
            if ($missing && $error === null) {
                return null;
            }

            throw new RankingException("{$name} {$this->key} failed: " . ($error ?? 'no result'));
        }

        return $result;
    }

    /** @return array{limit?: array{int, int}} */
    private static function limit(int $offset, ?int $count): array
    {
        return $offset === 0 && $count === null ? [] : ['limit' => [$offset, $count ?? -1]];
    }

    private function int(mixed $value): int
    {
        return \is_int($value) ? $value : throw new RankingException("Unexpected reply from Redis for {$this->key}: " . get_debug_type($value));
    }

    private function float(mixed $value): float
    {
        return \is_float($value) || \is_int($value) ? (float) $value : throw new RankingException("Unexpected reply from Redis for {$this->key}: " . get_debug_type($value));
    }

    /** @return list<string> */
    private function members(mixed $value): array
    {
        if (!\is_array($value)) {
            throw new RankingException("Unexpected reply from Redis for {$this->key}: " . get_debug_type($value));
        }

        return array_values(array_map(strval(...), array_filter($value, is_scalar(...))));
    }

    /** @return array<string|int, float> */
    private function scores(mixed $value): array
    {
        if (!\is_array($value)) {
            throw new RankingException("Unexpected reply from Redis for {$this->key}: " . get_debug_type($value));
        }

        return array_map(fn(mixed $score): float => $this->float($score), $value);
    }
}
