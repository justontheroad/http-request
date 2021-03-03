<?php

namespace justontheroad\HttpRequest;

/**
 * HTTP请求构建器接口
 * 
 */
interface HttpRequestBuilderInterface
{
    /**
     * GET 请求
     */
    const METHOD_GET  = 'GET';
    /**
     * POST 请求
     */
    const METHOD_POST = 'POST';
    /**
     * JSON POST 请求
     */
    // const METHOD_JSON = 'JSON';
    /**
     * PUT 请求
     */
    const METHOD_PUT  = 'PUT';
    /**
     * 默认超时时间 3秒
     */
    const DEFAULT_TIMEOUT = 3;

    /**
     * 设置请求选项
     *
     * @param array $options    请求选项
     */
    public function setOptions(array $options);

    /**
     * 构建参数
     *
     * @return array    request参数
     */
    public function build();

    /**
     * 构建选项
     *
     * @return array                    options参数
     */
    public function buildOptions();
}
