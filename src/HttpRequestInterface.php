<?php

namespace justontheroad\HttpRequest;

/**
 * HTTP请求接口
 */
interface HttpRequestInterface
{
    /**
     * 结果格式
     */
    const RESULT_FORMAT = [
        'uid'      => '',
        'url'      => '',
        'httpCode' => '',
        'content'  => ''
    ];
    /**
     * 发起请求
     *
     * @return void
     */
    public function request();
    /**
     * 获取结果
     *
     */
    public function getResult();
}
