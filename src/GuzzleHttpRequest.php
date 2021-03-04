<?php

namespace justontheroad\HttpRequest;

use justontheroad\HttpRequest\Guzzle\{
    BatchAsyncGuzzleHttpRequest,
    SyncGuzzleHttpRequest
};
// use \Exception as Exception;
use \InvalidArgumentException as InvalidArgumentException;

/**
 * Guzzle HTTP请求
 * 代理，隐藏 BatchAsyncGuzzleHttpRequest、SyncGuzzleHttpRequest的实现细节。
 * 增加 beforeRequest 和 afterRequest 方法
 * public function beforeRequest(array $requests)
 * {
 *     parent::beforeRequest($requests);
 *
 *     // 你可以在此自定义代码，如果需要在请求之前做一些过滤或者其他逻辑
 * }
 * 
 * public function afterRequest(array $requests)
 * {
 *     parent::afterRequest($requests);
 *
 *     // 你可以在此自定义代码，如果需要在请求之后做一些其他逻辑
 * }
 * 
 * HttpRequestBuilderInterface构建请求
 * 
 * {@see app\components\HttpRequest\HttpRequestBuilderInterface}
 * 
 * GuzzleHttpRequestRetryInterface实现重试功能
 * 
 * {@see app\components\HttpRequest\GuzzleHttpRequestRetryInterface}
 * 
 * HttpRequestRedisCacheInterface实现api缓存功能
 * 
 * {@see app\components\HttpRequest\HttpRequestRedisCacheInterface}
 * 
 * 
 * 缓存key生成算法。修改request的$uid可以试缓存立即失效
 * ```php
 *   function requestsToKey(array $requests)
 *   {
 *       $uids = [];
 *       foreach ($requests as $request) {
 *           $uids[] = $request->uid;
 *       }
 *
 *       return hash('sha256', serialize($uids));
 *   }
 * ```
 * 
 * .eg
 * ```php
 *   $client = GuzzleHttpRequest::create();
 *   $client->async(); // 异步
 *   $client
 *   ->setRetry(new GuzzleHttpRequestRetry(2, 1000))
 *   ->setCache(new HttpRequestRedisCache($redisInstance, 'base:http_request_api:', 60, 6))
 *   ->setOptions($options)
 *   ->appendRequest(
 *       new GuzzleHttpRequestBuilder(
 *       'http://www.base.com.master.php7.egomsl.com/api/pipeline/items',
 *       GuzzleHttpRequestBuilder::METHOD_POST,
 *       [
 *           'site'   => 'ZF',
 *           'apiId' => '81194069E82FFDFE',
 *           'token' => 'acc4fcecd5bc0e46b1849aedb69ccf38'
 *       ],
 *       $header,
 *       3)
 *   )
 *   ->appendRequest(
 *       new GuzzleHttpRequestBuilder(
 *       'http://www.base.com.master.php7.egomsl.com/api/category/items',
 *       GuzzleHttpRequestBuilder::METHOD_POST,
 *       [
 *           'site'   => 'ZF',
 *           'apiId' => '81194069E82FFDFE',
 *           'token' => 'acc4fcecd5bc0e46b1849aedb69ccf38'
 *       ],
 *       $header)
 *   );
 *
 *   $client->request();
 *   list($resultList, $failedList) = $client->getResult();
 * ```
 */
class GuzzleHttpRequest implements HttpRequestInterface
{
    /**
     * 是否异步
     *
     * @var boolean
     */
    private $_async;
    /**
     * 重设接口
     *
     * @var HttpRetryInterface
     */
    private $_retry;
    /**
     * 请求接口列表
     *
     * @var array HttpRequestInterface
     */
    private $_requests;
    /**
     * 缓存接口
     *
     * @var HttpRequestCacheInterface
     */
    private $_apiCache;
    /**
     * 请求结果
     *
     * @var array $resultList,$failedList
     */
    private $_result;

    /**
     * 创建
     * 静态工厂方法
     * 
     * @return static
     */
    public static function create()
    {
        $instance = new static();
        // 初始化
        $instance->_async    = false;
        $instance->_retry    = null;
        // $instance->_options  = [];
        $instance->_requests = [];
        $instance->_apiCache = null;
        $instance->_result   = [];

        return $instance;
    }

    /**
     * 异步
     *
     * @return static
     */
    public function async()
    {
        $this->_async = true;
        return $this;
    }

    /**
     * 设置重试
     *
     * @param HttpRetryInterface $retry 重设接口
     * @return static
     */
    public function setRetry(HttpRetryInterface $retry)
    {
        $this->_retry = $retry;
        return $this;
    }

    /**
     * 设置选项
     *
     * @param array $options   Guzzle选项
     * @return static
     */
    // public function setOptions(array $options)
    // {
    //     $this->_options = $options;
    //     return $this;
    // }

    /**
     * 设置缓存
     *
     * @param HttpRequestCacheInterface $apiCache   缓存接口
     * @return static
     */
    public function setCache(HttpRequestCacheInterface $apiCache)
    {
        $this->_apiCache = $apiCache;
        return $this;
    }

    /**
     * 追加请求
     *
     * @param string $url       url
     * @param string $method    请求方法
     * @param array $params     请求参数
     * @param array $headers    头信息
     * @param integer $timeout  超时
     * @param integer $cuid     用户自定义uid
     * @param static
     */
    // public function appendRequest(string $url, string $method, array $params = [], array $headers = [], int $timeout = 0, int $cuid = 0)
    // {
    //     $this->_requests[] = new GuzzleHttpRequestBuilder($url, $method, $params, $headers, $timeout, $cuid);
    //     return $this;
    // }

    /**
     * 追加请求
     *
     * @param HttpRequestBuilderInterface $request  请求 
     * @param static
     */
    public function appendRequest(HttpRequestBuilderInterface $request)
    {
        $this->_requests[] = $request;
        return $this;
    }

    /**
     * 设置请求
     *
     * @param HttpRequestBuilderInterface $request  请求 
     * @param static
     */
    public function setRequest(HttpRequestBuilderInterface $request)
    {
        unset($this->_requests);
        $this->_requests[] = $request;
        return $this;
    }

    /**
     * 清理请求和结果
     *
     * @return void
     */
    public function clear(): void
    {
        unset($this->_requests, $this->_result);
        $this->_requests = [];
        $this->_result   = [];
    }

    /**
     * 发起请求
     *
     * @return array            $resultList, $failedList
     * @throws GuzzleException, InvalidArgumentException,
     */
    public function request()
    {
        $requests = $this->_requests;
        // 检测请求对象
        if (empty($requests)) {
            throw new InvalidArgumentException('requests不能为空', 500);
        }
        if (!$this->checkRequests($requests)) {
            throw new InvalidArgumentException('Request Builder 对象有误，必须为HttpRequestBuilder', 500);
        }

        // 请求前执行
        $this->beforeRequest($requests);
        $result = null;

        if ($this->checkResult()) {
            // 从缓存中获取结果
            $result = $this->_result;
        } else {
            // 从缓存中获取不到结果
            $result      = [[], []]; // 0:success 1:failed
            $httpRequest = null;
            // 异步
            if ($this->_async) {
                $concurrency = count($requests);
                // 8 < $concurrency && $concurrency = 8;
                $httpRequest = new BatchAsyncGuzzleHttpRequest();
                $httpRequest
                    ->setConcurrency($concurrency);
                $this->hasSetRetry() && $httpRequest->setRetry($this->_retry);

                $uids = [];
                foreach ($requests as $request) {
                    $uids[] = $request->uid;
                    $httpRequest->appendRequest($request);
                }

                $resultList = null;
                $failedList = null;
                try {
                    $httpRequest->request();
                    list($resultList, $failedList) = $httpRequest->getResult();
                    //     // test throw exception
                    //     unset($failedList);
                    //     $failedList = [];
                    //     1 / 0;
                    // } catch (Exception $e) {
                } catch (\GuzzleHttp\Exception\RequestException $e) {
                    $response = $e->getResponse();
                    $key      = $request->uid;
                    $ruids    = array_merge(array_keys($resultList), array_keys($failedList));
                    $duids    = array_diff($uids, $ruids);
                    // 异常响应
                    foreach ($requests as $request) {
                        if (!in_array($request->uid, $duids)) {
                            continue;
                        }
                        $key = $request->uid;
                        $failedList[$key]             = HttpRequestInterface::RESULT_FORMAT;
                        $failedList[$key]['uid']      = $key;
                        $failedList[$key]['url']      = $request->url ?? '';
                        $failedList[$key]['httpCode'] = 504; // 504 Gateway Timeout
                        $failedList[$key]['content']  = $e->getMessage();
                        // $failedList[$key]['content']  = $e; // $e 中包含闭包，导致 serialize/unserialize 异常
                    }
                }

                $result = [$resultList, $failedList];
            } else {
                // 同步
                foreach ($requests as $request) {
                    $httpRequest = new SyncGuzzleHttpRequest();
                    $httpRequest->setRequest($request);
                    $this->hasSetRetry() && $httpRequest->setRetry($this->_retry);

                    $resultList = null;
                    $failedList = null;
                    try {
                        $httpRequest->request();
                        list($resultList, $failedList) = $httpRequest->getResult();
                    } catch (\GuzzleHttp\Exception\RequestException $e) {
                        $response = $e->getResponse();
                        $key      = $request->uid;

                        $failedList[$key]             = HttpRequestInterface::RESULT_FORMAT;
                        $failedList[$key]['uid']      = $key;
                        $failedList[$key]['url']      = $request->url ?? '';
                        $failedList[$key]['httpCode'] = method_exists($response, 'getStatusCode') ? $response->getStatusCode() : 504; // 504 Gateway Timeout
                        $failedList[$key]['content']  = $e->getMessage();
                    }
                    is_array($resultList) && $result[0] = array_merge($result[0], $resultList);
                    is_array($failedList) && $result[1] = array_merge($result[1], $failedList);
                }
            }
        }

        // 请求后执行
        $this->afterRequest($requests, $result);
    }

    /**
     * 获取结果
     *
     * @return array $resultList,$failedList
     */
    public function getResult()
    {
        return $this->_result;
    }

    /**
     * 此方法在请求执行之前被调用。
     * 默认执行获取接口缓存逻辑
     * 
     * public function beforeRequest(array $requests)
     * {
     *     parent::beforeRequest($requests);
     *
     *     // 你可以在此自定义代码，如果需要在请求之前做一些过滤或者其他逻辑
     * }
     *
     * @param array $requests   请求数组，HttpRequestBuilderInterface
     * @return void
     */
    protected function beforeRequest(array $requests)
    {
        // 从缓存中获取结果
        $rediskey      = $this->requestsToKey($requests);
        $this->_result = $this->resultFromCache($rediskey);
    }

    /**
     * 此方法在请求执行之后被调用。
     * 默认执行保存接口缓存逻辑
     *
     * public function afterRequest(array $requests)
     * {
     *     parent::afterRequest($requests);
     *
     *     // 你可以在此自定义代码，如果需要在请求之后做一些其他逻辑
     * }
     * @param array $requests   请求数组，HttpRequestBuilderInterface
     * @param array $result     请求结果，$resultList,$failedList
     * @return void
     */
    protected function afterRequest(array $requests, array $result)
    {
        if (!$this->checkResult() && 2 == count($result)) {
            // 不是从缓存中获取的或者数据无效，需要设置缓存
            $this->_result = $result;
            $rediskey = $this->requestsToKey($requests);
            $this->resultToCache($rediskey, $result);
        }
    }

    /**
     * 检查结果
     *
     * @return boolean  是否合法
     */
    private function checkResult(): bool
    {
        return 2 == count($this->_result) ? true : false;
    }

    /**
     * 从缓存中获取结果
     *
     * @param string $skey          key
     * @return array $resultList, $failedList
     */
    private function resultFromCache(string $key)
    {
        if (!$this->hasSetCache()) {
            return [];
        }

        $data = $this->_apiCache->get($key);
        if (false !== $data) {
            $data = $data;
            return $data;
        }

        return [];
    }

    /**
     * 结果保存至缓存
     *
     * @param string $skey      key
     * @param array $result     结果数组
     * @return void
     */
    private function resultToCache(string $key, array $result): void
    {
        if (!$this->hasSetCache()) {
            return;
        }

        $this->_apiCache->set($key, $result);
    }

    /**
     * 请求转key
     *
     * @param array $requests   请求数组，HttpRequestBuilderInterface
     * @return string           key
     */
    private function requestsToKey(array $requests)
    {
        $uids = [];
        foreach ($requests as $request) {
            $uids[] = $request->uid;
        }

        return hash('sha256', serialize($uids));
    }

    /**
     * 检测请求数组
     *
     * @param array $requests   请求数组，HttpRequestBuilderInterface
     * @return boolean          是否合法
     */
    private function checkRequests(array $requests): bool
    {
        foreach ($requests as $request) {
            if (!($request instanceof GuzzleHttpRequestBuilder)) {
                return false;
            }
        }

        return true;
    }

    /**
     * 有设置重试
     *
     * @return boolean  是否设置了重设
     */
    private function hasSetRetry()
    {
        return $this->_retry instanceof HttpRetryInterface ? true : false;
    }

    /**
     * 有设置缓存
     *
     * @return boolean  是否设置了缓存
     */
    private function hasSetCache()
    {
        return $this->_apiCache instanceof HttpRequestCacheInterface ? true : false;
    }
}
