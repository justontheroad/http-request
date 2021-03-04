<?php

namespace justontheroad\HttpRequest;

use \Exception as Exception;

/**
 * HTTP请求缓存
 * 
 */
class HttpRequestRedisCache implements HttpRequestCacheInterface
{
    /**
     * 时间线
     * start => 开始时间，end => 结束时间
     *
     * @var array
     */
    public $timeline;
    /**
     * redis服务
     *
     * @var object
     */
    private $_redis = null;
    /**
     * redis key 前缀
     *
     * @var string
     */
    private $_redisKeyPrefix;
    /**
     * 最大请求数
     * 超过请求数自动清理缓存
     * 保留属性
     * @var integer
     */
    private $_maximumRequest;
    /**
     * 空权限过期时间
     *
     * @var integer
     */
    private $_emptyExpire;
    /**
     * 权限过期时间
     *
     * @var integer
     */
    private $_expire;

    /**
     * 构造函数
     *
     * @param mixed $redis              redis 实例
     * @param string $redisKeyPrefix    redis key 前缀，默认 base:http_request_api
     * @param integer $expire           数据过期时间，默认 60s
     * @param integer $emptyExpire      空值或异常时的过期时间，默认 6s
     * @throws Exception                必要配置异常
     */
    public function __construct($redis, string $redisKeyPrefix = 'base:http_request_api:', int $expire = 60, int $emptyExpire = 6)
    {
        $this->_redis          = $redis;
        $this->_redisKeyPrefix = $redisKeyPrefix;
        $this->_expire         = $expire;
        $this->_emptyExpire    = $emptyExpire;

        if (!method_exists($this->_redis, 'get') || !method_exists($this->_redis, 'setex') || !method_exists($this->_redis, 'del')) {
            throw new Exception('redis 实例缺少方法，get|setex|del', 500);
        }
    }

    /**
     * 设置
     *
     * @param string $key       key
     * @param mixed  $value     值
     * @param integer $timeout  超时
     * @return integer          是否成功
     */
    public function set(string $key, $value)
    {
        $key = $this->getKey($key);
        if (!empty($value)) {
            $ret = $this->_redis->setex($key, $this->_expire, serialize($value));
        } else {
            $ret = $this->_redis->setex($key, $this->_emptyExpire, '');
        }

        return $ret;
    }

    /**
     * 获取
     *
     * @param string $key       key
     * @return array|null       数据
     */
    public function get(string $key)
    {
        $key  = $this->getKey($key);
        $data = $this->_redis->get($key);
        if (is_null($data) || false === $data) {
            return null;
        } else if (empty($data)) {
            return [];
        }

        return unserialize($data);
    }

    /**
     * 删除
     *
     * @param string $key       key
     * @return boolean          是否删除成功
     */
    public function delete(string $key)
    {
        $key = $this->getKey($key);
        $this->_redis->del($key);
    }

    /**
     * 获取key
     *
     * @param string $key           原始key
     * @return string               组合之后的key
     */
    private function getKey(string $key): string
    {
        if ($this->_redisKeyPrefix[strlen($this->_redisKeyPrefix) - 1] != ':') {
            return $this->_redisKeyPrefix . $key;
        }

        return  $this->_redisKeyPrefix . ":{$key}";
    }
}