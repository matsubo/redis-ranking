<?php
require __DIR__ . '/../vendor/autoload.php';
/**
 * RankingTest
 *
 * @version $id$
 * @copyright Yuki Matsukura
 * @author Yuki Matsukura <matsubokkuri@gmail.com>
 * @license PHP Version 3.0 {@link http://www.php.net/license/3_0.txt}
 */
use Matsubo\Redis\Ranking;
class RankingTest extends \PHPUnit_Framework_TestCase
{
    private $ranking;

    /**
     * Provider for user id
     *
     * @return array
     */
    public function userProvider()
    {
        $data = array(
            array('kanako',    100), // 3
            array('reni',        0), // 0
            array('momoka',    100), // 3
            array('akari',      50), // 1
            array('ayaka',    1000), // 4
        );

        return $data;
    }


    /**
     * setUp
     *
     * @access public
     * @return void
     */
    public function setUp()
    {
        $this->ranking = new Ranking;
    }
    /**
     * @dataProvider userProvider
     */
    public function testUserScore1($user_id, $score)
    {
        $return = $this->ranking->setUserScore($user_id, $score);
        $this->assertEquals(1, $return);
    }
    /**
     * @depends testUserScore1
     */
    public function testScore()
    {
        $score = $this->ranking->getScore($user_id = 'akari');
        $this->assertEquals(50, $score);
    }
    /**
     * @depends testUserScore1
     * @dataProvider userProvider
     */
    public function testIncrementScore($user_id, $score_diff)
    {
        $current = $this->ranking->getScore($user_id);

        $new_value = $this->ranking->incrementScore($user_id, $score_diff);
        $this->assertEquals($current + $score_diff, $new_value);
    }
    /**
     * @depends testUserScore1
     */
    public function testRank()
    {
        $rank = $this->ranking->getRank($user_id = 'akari');
        $this->assertEquals(1, $rank);
    }
    /**
     * @depends testUserScore1
     */
    public function testSize()
    {
        $size = $this->ranking->countUsers();
        $this->assertEquals(5, $size);
    }
    /**
     * @depends testUserScore1
     */
    public function testRange()
    {
        $hash = $this->ranking->getRange($start = 2, $end = 1, $withscores = true);
        $this->assertEquals(abs($end) + 1, count($hash));
    }
     /**
     * @depends testUserScore1
     */
    public function testRevRange()
    {
        $hash = $this->ranking->getRevRange($start = 2, $end = 1, $withscores = true);
        $this->assertEquals(abs($end) + 1, count($hash));
    }
     /**
     * @depends testUserScore1
     */
    public function testRangeByScores()
    {
        $hash = $this->ranking->getRangeByScores($start = 2, $end = 100000, $withscores = true, $offset = 1, $count = 3);
        $this->assertTrue(is_numeric(count($hash)));
    }
    /**
     * @dataProvider userProvider
     * @depends testSize
     */
    public function testDelete($user_id, $score)
    {
        $return = $this->ranking->deleteUser($user_id);
        $this->assertEquals(Ranking::STATUS_OK, $return);
    }
    /**
     * @dataProvider userProvider
     * @depends testDelete
     */
    public function testUserScore2($user_id, $score)
    {
        $return = $this->ranking->setUserScore($user_id, $score);
        $this->assertEquals(Ranking::STATUS_OK, $return);
    }
    /**
     *
     */
    public function testDeleteAllUser()
    {
        $deleted_user_id = $this->ranking->deleteAllUser();
        $this->assertEquals(5, count($deleted_user_id));
    }
    public function tearDown()
    {
    }
}
