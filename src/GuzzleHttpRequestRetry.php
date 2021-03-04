<?php

namespace justontheroad\HttpRequest;

// use app\components\HttpRequest\HttpRetryInterface;
use GuzzleHttp\Psr7\{
    Request,
    Response
};
use GuzzleHttp\Exception\{
    // ClientException
    RequestException,
    ConnectException
};

/**
 * Guzzle HTTP 请求重试
 * 
 */
class GuzzleHttpRequestRetry implements HttpRetryInterface
{
    /**
     *  最大重试次数
     *  默认3次
     *
     * @var integer
     */
    private $_maxRetries;
    /**
     * 延时时间，毫秒
     * 默认1000毫秒
     *
     * @var integer
     */
    private $_delay;

    public function __construct(int $maxRetries = 3, int $delay = 1000)
    {
        $this->_maxRetries = $maxRetries;
        $this->_delay      = $delay;
    }

    /**
     * 设置最大重试次数
     *
     * @param integer $maxRetries       最大重试次数
     * @return void
     */
    public function setMaxRetries(int $maxRetries)
    {
        $this->_maxRetries = $maxRetries;
    }

    /**
     * 设置延时
     *
     * @param integer $delay            延时
     * @return void
     */
    public function setDelay(int $delay)
    {
        $this->_delay = $delay;
    }

    /**
     * 返回一个匿名函数, 匿名函数若返回false 表示不重试，反之则表示继续重试
     * 
     * @return Closure
     */
    public function retryDecider()
    {
        return function ($retries, Request $request, Response $response = null, RequestException $exception = null) {
            // 超过最大重试次数，不再重试
            if ($retries >= $this->_maxRetries) {
                return false;
            }

            // 请求失败，继续重试
            if ($exception instanceof ConnectException) {
                // if ($exception instanceof ConnectException || $exception instanceof \Exception) { // 临时测试异常使用
                //     var_dump($request->getRequestTarget(), $retries);
                return true;
            }

            if ($response) {
                // 如果请求有响应，但是状态码大于等于500，继续重试(这里根据自己的业务而定)
                if ($response->getStatusCode() >= 500) {
                    return true;
                }
            }

            return false;
        };
    }

    /**
     * 返回一个匿名函数，该匿名函数返回下次重试的时间（毫秒）
     * 
     * @return Closure
     */
    public function retryDelay()
    {
        return function ($numberOfRetries) {
            return $this->_delay * $numberOfRetries;
        };
    }
}
