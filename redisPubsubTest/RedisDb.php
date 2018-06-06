<?php
/**
 * Created by PhpStorm.
 * User: zheng jinwei
 * Date: 2017/3/8
 * Time: 11:38
 */

class RedisDb
{

    // 是否使用 M/S 的读写集群方案
    private $_isUseCluster = false;

    // Slave 句柄标记
    private $_sn = 0;

    // 服务器连接句柄
    private $_linkHandle = array(
        'master' => null,// 只支持一台 Master
        'slave' => array(),// 可以有多台 Slave
    );

    /**
     * redis credentials
     * @var string
     */
    protected $host;
    protected $port;
    protected $db;
    protected $auth;
    protected $pconnect;

    /**
     * 构造函数
     * @param array $config Redis服务器配置
     * @param boolean $isMaster 当前添加的服务器是否为 Master 服务器
     * @param boolean $isUseCluster 是否采用 M/S 方案
     */
    public function __construct($config = array('host' => '127.0.0.1', 'port' => 6379, 'db' => 0, 'auth' => null, 'pconnect' => false), $isMaster = true, $isUseCluster = false)
    {
        $this->_isUseCluster = $isUseCluster;
        $this->host = empty($config['host']) ? '127.0.0.1' : $config['host'];
        $this->port = empty($config['port']) ? 6379 : $config['port'];
        $this->db = !isset($config['db']) ? 0 : $config['db'];
        $this->auth = $config['auth'];
        $this->pconnect = $config['pconnect'];

        $this->connect($isMaster);
    }

    /**
     * 连接服务器,注意：这里使用长连接，提高效率，但不会自动关闭
     * @param boolean $isMaster 当前添加的服务器是否为 Master 服务器
     * @return boolean
     */
    public function connect($isMaster = true)
    {
        $redis = new \Redis();
        if ($this->pconnect) {
            $ret = $redis->pconnect($this->host, $this->port);
        } else {
            $ret = $redis->connect($this->host, $this->port);
        }

        if (isset($this->auth) && $this->auth) {
            $redis->auth($this->auth);
        }

        // 选择数据库
        $redis->select($this->db);

        // 设置 Master 连接
        if ($isMaster) {
            $this->_linkHandle['master'] = $redis;
        } else {
            $this->_linkHandle['slave'][$this->_sn] = $redis;
            ++$this->_sn;
        }

        return $ret;
    }


    /**获取数据库服务器状态
     * @return bool
     */
    public function ping()
    {
        $ret = $this->getRedis()->ping();
        if (strtolower($ret) == '+pong') {
            return true;
        }
        return false;
    }

    /**
     * 关闭连接
     *
     * @param int $flag 关闭选择 0:关闭 Master 1:关闭 Slave 2:关闭所有
     * @return boolean
     */
    public function close($flag = 2)
    {
        switch ($flag) {
            // 关闭 Master
            case 0:
                $this->getRedis()->close();
                break;
            // 关闭 Slave
            case 1:
                for ($i = 0; $i < $this->_sn; ++$i) {
                    $this->_linkHandle['slave'][$i]->close();
                }
                break;
            // 关闭所有
            case 2:
                $this->getRedis()->close();
                for ($i = 0; $i < $this->_sn; ++$i) {
                    $this->_linkHandle['slave'][$i]->close();
                }
                break;
        }
        return true;
    }

    public function __destruct()
    {
        $this->close(2);
    }

    /**
     * 得到 Redis 原始对象可以有更多的操作
     *
     * @param boolean $isMaster 返回服务器的类型 true:返回Master false:返回Slave
     * @param boolean $slaveOne 返回的Slave选择 true:负载均衡随机返回一个Slave选择 false:返回所有的Slave选择
     * @return redis object
     */
    public function getRedis($isMaster = true, $slaveOne = true)
    {
        // 只返回 Master
        if ($isMaster) {
            return $this->_linkHandle['master'];
        } else {
            return $slaveOne ? $this->_getSlaveRedis() : $this->_linkHandle['slave'];
        }
    }


    public function exists($key)
    {
        return $this->getRedis()->exists($key);
    }

    public function publish($channel,$msg){
        $this->getRedis()->publish($channel,$msg);
    }


    public function subscribe($channels,$callbackFuncName){
        $this->getRedis()->subscribe($channels,$callbackFuncName);
    }

    /**
     * 写缓存
     *
     * @param string $key 组存KEY
     * @param string $value 缓存值
     * @param int $expire 过期时间， 0:表示无过期时间
     */
    public function set($key, $value, $expire = 0)
    {
        // 永不超时
        if ($expire == 0) {
            $ret = $this->getRedis()->set($key, $value);
        } else {
            $ret = $this->getRedis()->setex($key, $expire, $value);
        }
        return $ret;
    }

    /**
     * 读缓存
     *
     * @param string $key 缓存KEY,支持一次取多个 $key = array('key1','key2')
     * @return string || boolean  失败返回 false, 成功返回字符串
     */
    public function get($key)
    {
        // 是否一次取多个值
        $func = is_array($key) ? 'mGet' : 'get';
        // 没有使用M/S
        if (!$this->_isUseCluster) {
            return $this->getRedis()->{$func}($key);
        }
        // 使用了 M/S
        return $this->_getSlaveRedis()->{$func}($key);
    }


    /*
        // magic function
        public function __call($name,$arguments){
            return call_user_func($name,$arguments);
        }
    */
    /**
     * 条件形式设置缓存，如果 key 不存时就设置，存在时设置失败
     *
     * @param string $key 缓存KEY
     * @param string $value 缓存值
     * @return boolean
     */
    public function setnx($key, $value)
    {
        return $this->getRedis()->setnx($key, $value);
    }

    /**
     * 删除缓存
     *
     * @param string || array $key 缓存KEY，支持单个健:"key1" 或多个健:array('key1','key2')
     * @return int 删除的健的数量
     */
    public function remove($key)
    {
        // $key => "key1" || array('key1','key2')
        return $this->getRedis()->delete($key);
    }

    /**
     * 值加加操作,类似 ++$i ,如果 key 不存在时自动设置为 0 后进行加加操作
     *
     * @param string $key 缓存KEY
     * @param int $default 操作时的默认值
     * @return int　操作后的值
     */
    public function incr($key, $default = 1)
    {
        if ($default == 1) {
            return $this->getRedis()->incr($key);
        } else {
            return $this->getRedis()->incrBy($key, $default);
        }
    }

    /**
     * 值减减操作,类似 --$i ,如果 key 不存在时自动设置为 0 后进行减减操作
     *
     * @param string $key 缓存KEY
     * @param int $default 操作时的默认值
     * @return int　操作后的值
     */
    public function decr($key, $default = 1)
    {
        if ($default == 1) {
            return $this->getRedis()->decr($key);
        } else {
            return $this->getRedis()->decrBy($key, $default);
        }
    }

    /**
     * 添空当前数据库
     *
     * @return boolean
     */
    public function clear()
    {
        return $this->getRedis()->flushDB();
    }

    /**
     *    lpush
     */
    public function lpush($key, $value)
    {
        return $this->getRedis()->lpush($key, $value);
    }

    /**
     *    add lpop
     */
    public function lpop($key)
    {
        return $this->getRedis()->lpop($key);
    }

    /**
     *    rpush
     */
    public function rpush($key, $value)
    {
        return $this->getRedis()->rpush($key, $value);
    }

    /**
     *    add rpop
     */
    public function rpop($key)
    {
        return $this->getRedis()->rpop($key);
    }

    /**
     * lrange
     */
    public function lrange($key, $start, $end)
    {
        return $this->getRedis()->lRange($key, $start, $end);
    }

    /**
     * lrange
     */
    public function ltrim($key, $start, $end)
    {
        return $this->getRedis()->ltrim($key, $start, $end);
    }

    public function hmset($key, $values)
    {
        return $this->getRedis()->hmset($key, $values);
    }

    public function hmget($key, $fields)
    {
        return $this->getRedis()->hmget($key, $fields);
    }

    /**
     *    set hash opeation
     */
    public function hset($name, $key, $value)
    {
        if (is_array($value)) {
            return $this->getRedis()->hset($name, $key, serialize($value));
        }
        return $this->getRedis()->hset($name, $key, $value);
    }

    /**
     *    set hash opeation
     */
    public function hsetnx($name, $key, $value)
    {
        if (is_array($value)) {
            return $this->getRedis()->hsetnx($name, $key, serialize($value));
        }
        return $this->getRedis()->hsetnx($name, $key, $value);
    }

    /**
     *    hIncrBy
     */
    public function hIncrBy($name, $key, $value)
    {
        return $this->getRedis()->hIncrBy($name, $key, $value);
    }

    /**
     *    get hash opeation
     */
    public function hget($name, $key = null, $serialize = true)
    {
        if ($key) {
            $row = $this->getRedis()->hget($name, $key);
            if ($row && $serialize) {
                unserialize($row);
            }
            return $row;
        }
        return $this->getRedis()->hgetAll($name);
    }

    public function hgetAll($key)
    {
        return $this->getRedis()->hgetAll($key);
    }

    /**
     *    delete hash opeation
     */
    public function hdel($name, $key = null)
    {
        if ($key) {
            return $this->getRedis()->hdel($name, $key);
        }
        return $this->getRedis()->hdel($name);
    }

    /**
     *  ttl
     */
    public function ttl($key)
    {
        return $this->getRedis()->ttl($key);
    }

    /**
     *  expire
     */
    public function expire($key, $seconds)
    {
        return $this->getRedis()->expire($key, $seconds);
    }

    /**
     *  expireat
     */
    public function expireat($key, $tm)
    {
        return $this->getRedis()->expire($key, $tm);
    }

    /**
     * Transaction start
     */
    public function multi()
    {
        return $this->getRedis()->multi();
    }

    /**
     * Transaction send
     */

    public function exec()
    {
        return $this->getRedis()->exec();
    }

    public function  discard()
    {
        return $this->getRedis()->discard();
    }

    public function zAdd($key, $score, $value)
    {
        return $this->getRedis()->zAdd($key, $score, $value);
    }

    public function zRangeByScore($key, $min, $max)
    {
        return $this->getRedis()->zRangeByScore($key, $min, $max);
    }

    public function zRange($key, $min, $max)
    {
        return $this->getRedis()->zRange($key, $min, $max);
    }

    public function zSize($key)
    {
        return $this->getRedis()->zSize($key);
    }

    public function zCard($key)
    {
        return $this->getRedis()->zCard($key);
    }

    public function zCount($key,$start,$end)
    {
        return $this->getRedis()->zCount($key,$start,$end);
    }

    /* =================== 以下私有方法 =================== */

    /**
     * 随机 HASH 得到 Redis Slave 服务器句柄
     *
     * @return redis object
     */
    private function _getSlaveRedis()
    {
        // 就一台 Slave 机直接返回
        if ($this->_sn <= 1) {
            return $this->_linkHandle['slave'][0];
        }
        // 随机 Hash 得到 Slave 的句柄
        $hash = $this->_hashId(mt_rand(), $this->_sn);
        return $this->_linkHandle['slave'][$hash];
    }

    /**
     * 根据ID得到 hash 后 0～m-1 之间的值
     *
     * @param string $id
     * @param int $m
     * @return int
     */
    private function _hashId($id, $m = 10)
    {
        //把字符串K转换为 0～m-1 之间的一个值作为对应记录的散列地址
        $k = md5($id);
        $l = strlen($k);
        $b = bin2hex($k);
        $h = 0;
        for ($i = 0; $i < $l; $i++) {
            //相加模式HASH
            $h += substr($b, $i * 2, 2);
        }
        $hash = ($h * 1) % $m;
        return $hash;
    }

}// End Class