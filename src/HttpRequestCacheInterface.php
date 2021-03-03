<?php

namespace justontheroad\HttpRequest;

/**
 * HTTP请求缓存接口
 * 
 */
interface HttpRequestCacheInterface
{
    /**
     * 设置
     *
     * @param string $key       key
     * @param mixed  $value     值
     * @return
     */
    public function set(string $key, $value);
    /**
     * 获取
     *
     * @param string $key       key
     * @return
     */
    public function get(string $key);
    /**
     * 删除
     *
     * @param string $key       key
     * @return
     */
    public function delete(string $key);
}
