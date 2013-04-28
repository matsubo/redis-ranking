<?php
namespace Matsubo\Redis;
/**
 * Ranking
 *
 * @author Yuki Matsukura <matsubokkuri@gmail.com>
 * @license PHP Version 3.0 {@link http://www.php.net/license/3_0.txt}
 */
class Ranking
{
    /** @const result status of ok */
    const STATUS_OK = 1;
    /** @const result status of failure */
    const STATUS_FAILURE = 0;

    /** @var mixed Redis instance */
    private $redis;

    /** @var string key name for redis. */
    private $namespace = '';

    /**
     * __construct
     *
     * @access public
     * @param mixed Redis(optional)
     * @return void
     */
    public function __construct($namespace = '', $redis = null)
    {
        if (!$redis) {
            $redis = new \Redis();
            $redis->pconnect('127.0.0.1', 6379);
        }
        $this->redis     = $redis;
        $this->namespace = $namespace;
    }
    /**
     * setUserScore
     *
     * @param int $user_id
     * @param int $score
     * @access public
     * @return void
     */
    public function setUserScore($user_id, $score)
    {
        return $this->redis->zadd($this->namespace, $score, $user_id);
    }
    /**
     * getScore
     *
     * @param int $user_id
     * @access public
     * @return void
     */
    public function getScore($user_id)
    {
        return $this->redis->zScore($this->namespace, $user_id);
    }
    /**
     * getRank
     *
     * @param int $user_id
     * @access public
     * @return void
     */
    public function getRank($user_id)
    {
        return $this->redis->zRank($this->namespace, $user_id);
    }
    /**
     * incrementScore
     *
     * @param int $user_id
     * @param int $score_diff
     * @access public
     * @return void
     */
    public function incrementScore($user_id, $score_diff)
    {
        return $this->redis->zIncrBy($this->namespace, $score_diff, $user_id);
    }
    /**
     * getRange
     *
     * @param int $start
     * @param int $end
     * @param bool $withscores
     * @access public
     * @return void
     */
    public function getRange($start = 0, $end = -1, $withscores = false)
    {
        return $this->redis->zRange($this->namespace, $start, $end, $withscores);
    }
    /**
     * getRevRange
     *
     * @param int $start
     * @param int $end
     * @param bool $withscores
     * @access public
     * @return void
     */
    public function getRevRange($start = 0, $end = -1, $withscores = false)
    {
        return $this->redis->zRevRange($this->namespace, $start, $end, $withscores);
    }
    /**
     * getRangeByScores
     *
     * @param int $start
     * @param int $end
     * @param bool $withscores
     * @param int $offset
     * @param int $count
     * @access public
     * @return void
     */
    public function getRangeByScores($start = 0, $end = -1, $withscores = false, $offset = null, $count = null)
    {
        $options = array();
        $options['withscores'] = $withscores;
        if ($offset && $count) {
            $options['limit'] = array($offset, $count);
        }

        return $this->redis->zRangeByScore($this->namespace, $start, $end, $options);
    }
    /**
     * deleteUser
     *
     * @param int $user_id
     * @access public
     * @return void
     */
    public function deleteUser($user_id)
    {
        return $this->redis->zDelete($this->namespace, $user_id);
    }
    /**
     * deleteAllUser
     *
     * @access public
     * @return void
     */
    public function deleteAllUser()
    {
        $deleted_user_id = array();
        foreach ($this->redis->zRange($this->namespace, 0, -1) as $user_id) {
            $this->redis->zDelete($this->namespace, $user_id);
            $deleted_user_id[] = $user_id;
        }
        return $deleted_user_id;
    }
    /**
     * countUsers
     *
     * @access public
     * @return void
     */
    public function countUsers()
    {
        return $this->redis->zSize($this->namespace);
    }
}


