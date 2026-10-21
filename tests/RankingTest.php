<?php

declare(strict_types=1);

namespace Matsubo\Redis\Tests;

use Matsubo\Redis\ExpireMode;
use Matsubo\Redis\Ranking;
use Matsubo\Redis\RankingException;
use PHPUnit\Framework\TestCase;

/**
 * Needs a Redis server: REDIS_HOST / REDIS_PORT (default 127.0.0.1:6379).
 * Every test uses its own key and deletes it afterwards.
 */
final class RankingTest extends TestCase
{
    private \Redis $redis;

    private string $key;

    private Ranking $ranking;

    protected function setUp(): void
    {
        $this->redis = new \Redis();
        $this->redis->connect(getenv('REDIS_HOST') ?: '127.0.0.1', (int) (getenv('REDIS_PORT') ?: 6379));
        $this->key = 'ranking-test:' . bin2hex(random_bytes(6));
        $this->ranking = new Ranking($this->key, $this->redis);
    }

    protected function tearDown(): void
    {
        $this->redis->del($this->key);
    }

    private function seed(): void
    {
        foreach (['reni' => 0, 'akari' => 50, 'kanako' => 100, 'ayaka' => 1000, 'shiori' => 1001] as $user => $score) {
            $this->ranking->setUserScore($user, $score);
        }
    }

    public function testSetUserScoreTellsWhetherTheUserIsNew(): void
    {
        self::assertTrue($this->ranking->setUserScore('kanako', 100));
        self::assertFalse($this->ranking->setUserScore('kanako', 200));
        self::assertSame(200.0, $this->ranking->getScore('kanako'));
    }

    public function testMissingUsersAreNull(): void
    {
        $this->seed();

        self::assertNull($this->ranking->getScore('nobody'));
        self::assertNull($this->ranking->getRank('nobody'));
        self::assertNull($this->ranking->getRevRank('nobody'));
    }

    public function testAUserWithScoreZeroIsNotMissing(): void
    {
        $this->seed();

        self::assertSame(0.0, $this->ranking->getScore('reni'));
        self::assertSame(0, $this->ranking->getRank('reni'));
    }

    public function testRanksCountFromBothEnds(): void
    {
        $this->seed();

        self::assertSame(1, $this->ranking->getRank('akari'));
        self::assertSame(3, $this->ranking->getRevRank('akari'));
        self::assertSame(0, $this->ranking->getRevRank('shiori'));
    }

    public function testIncrementScoreStartsMissingUsersAtZero(): void
    {
        self::assertSame(5.0, $this->ranking->incrementScore('momoka', 5));
        self::assertSame(3.5, $this->ranking->incrementScore('momoka', -1.5));
    }

    public function testRanges(): void
    {
        $this->seed();

        self::assertSame(['reni', 'akari', 'kanako', 'ayaka', 'shiori'], $this->ranking->getRange());
        self::assertSame(['kanako', 'ayaka'], $this->ranking->getRange(2, 3));
        self::assertSame(['shiori', 'ayaka'], $this->ranking->getRevRange(0, 1));
        self::assertSame(['akari' => 50.0, 'kanako' => 100.0], $this->ranking->getRangeWithScores(1, 2));
        self::assertSame(['shiori' => 1001.0], $this->ranking->getRevRangeWithScores(0, 0));
    }

    public function testNumericUserIdsKeepTheirOrder(): void
    {
        $this->ranking->setUserScore(42, 2);
        $this->ranking->setUserScore('7', 1);

        self::assertSame(['7', '42'], $this->ranking->getRange());
        self::assertSame([7 => 1.0, 42 => 2.0], $this->ranking->getRangeWithScores());
        self::assertSame(1, $this->ranking->getRank(42));
    }

    public function testRangeByScores(): void
    {
        $this->seed();

        self::assertSame(['akari', 'kanako', 'ayaka'], $this->ranking->getRangeByScores(1, 1000));
        self::assertSame(['kanako', 'ayaka'], $this->ranking->getRangeByScores('(50', 1000));
        self::assertSame(['ayaka', 'shiori'], $this->ranking->getRangeByScores(1000, '+inf'));
        self::assertSame(['akari' => 50.0], $this->ranking->getRangeByScoresWithScores('-inf', '+inf', 1, 1));
    }

    public function testRangeByScoresHonoursACountWithOffsetZero(): void
    {
        // 1.x dropped the limit when the offset was 0.
        $this->seed();

        self::assertSame(['reni', 'akari'], $this->ranking->getRangeByScores('-inf', '+inf', 0, 2));
    }

    public function testCounts(): void
    {
        self::assertSame(0, $this->ranking->countUsers());
        $this->seed();

        self::assertSame(5, $this->ranking->countUsers());
        self::assertSame(2, $this->ranking->countUsersByScores(50, 100));
    }

    public function testDeleteUser(): void
    {
        $this->seed();

        self::assertTrue($this->ranking->deleteUser('akari'));
        self::assertFalse($this->ranking->deleteUser('akari'));
        self::assertSame(4, $this->ranking->countUsers());
    }

    public function testDeleteUsersByScoresAndRanks(): void
    {
        $this->seed();

        self::assertSame(2, $this->ranking->deleteUsersByScores('-inf', 50));
        self::assertSame(['kanako', 'ayaka', 'shiori'], $this->ranking->getRange());
        self::assertSame(1, $this->ranking->deleteUsersByRanks(-1, -1));
        self::assertSame(['kanako', 'ayaka'], $this->ranking->getRange());
    }

    public function testDeleteAllUserReturnsWhoWasThere(): void
    {
        $this->seed();

        self::assertSame(['reni', 'akari', 'kanako', 'ayaka', 'shiori'], $this->ranking->deleteAllUser());
        self::assertSame(0, $this->ranking->countUsers());
        self::assertSame(0, $this->redis->exists($this->key));
    }

    public function testExpireAndTtl(): void
    {
        self::assertFalse($this->ranking->expire(60_000), 'no key yet');
        self::assertNull($this->ranking->ttl());

        $this->seed();
        self::assertNull($this->ranking->ttl());
        self::assertTrue($this->ranking->expire(60_000, ExpireMode::IfNone));
        self::assertFalse($this->ranking->expire(120_000, ExpireMode::IfNone), 'already has an expiry');

        $ttl = $this->ranking->ttl();
        self::assertNotNull($ttl);
        self::assertGreaterThan(59_000, $ttl);
        self::assertLessThanOrEqual(60_000, $ttl);

        self::assertTrue($this->ranking->expire(120_000, ExpireMode::IfLater));
        self::assertGreaterThan(60_000, $this->ranking->ttl());
    }

    public function testAKeyOfAnotherTypeIsAnError(): void
    {
        $this->redis->set($this->key, 'not a sorted set');

        $this->expectException(RankingException::class);
        $this->expectExceptionMessage('WRONGTYPE');
        $this->ranking->getScore('kanako');
    }

    public function testRefusesToRunInsideMulti(): void
    {
        $this->redis->multi();
        try {
            $this->expectException(\LogicException::class);
            $this->ranking->setUserScore('kanako', 1);
        } finally {
            $this->redis->discard();
        }
    }
}
