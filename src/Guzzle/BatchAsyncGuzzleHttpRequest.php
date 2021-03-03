<?php

namespace justontheroad\HttpRequest\Guzzle;

use justontheroad\HttpRequest\{
    HttpRequestInterface,
    HttpRequestBuilderInterface
};
use GuzzleHttp\{
    Pool,
    Client,
    // Psr7\Request,
    Psr7\Response,
    HandlerStack,
    Middleware,
    Exception\RequestException
};

/**
 * 批量Guzzle HTTP异步请求
 * 
 * @method BatchAsyncGuzzleHttpRequest setConcurrency()，用于设置并发数，一般不超过总的请求数量
 * @method string appendRequest()，用于追加请求
 * @method void request()，执行请求
 * @method array getResult()，获取结果，@return array $resultList,$failedList
 */
class BatchAsyncGuzzleHttpRequest extends AbstractGuzzleHttpRequest implements HttpRequestInterface
{
    /**
     * 并发数
     * 默认4
     *
     * @var integer
     */
    private $_concurrency = 4;

    /**
     * 设置并发数
     *
     * @param integer $concurrency  并发数
     * @return HttpRequestInterface
     */
    public function setConcurrency(int $concurrency): HttpRequestInterface
    {
        $this->_concurrency = $concurrency;
        return $this;
    }

    /**
     * 追加请求
     *
     * @param string $url       url
     * @param array $params     参数
     * @param array $header     头信息
     * @return string           $uid
     */
    // public function appendRequest(string $url, string $method, array $params = [], array $header = [], int $timeout = 0)
    // {
    //     $request = new GuzzleHttpRequestBuilder($url, $method, $params, $header);
    //     $request->timeout = $timeout;
    //     $this->_requestList[$request->uid] = $request;
    //     return $request->uid;
    // }

    /**
     * 追加请求
     *
     * @param HttpRequestBuilderInterface $request  请求 
     * @return string                               $uid
     */
    public function appendRequest(HttpRequestBuilderInterface $request)
    {
        $this->_requestList[$request->uid] = $request;
        return $request->uid;
    }

    /**
     * @inheritDoc
     * 
     * @throws Exception
     */
    public function request()
    {
        // $this->setRetry(new GuzzleHttpRequestRetry(3));
        $handler = HandlerStack::create();
        // 创建重试中间件，指定决策者，指定重试延迟
        if ($this->hasSetRetry()) {
            $handler->push(Middleware::retry($this->_retry->retryDecider(), $this->_retry->retryDelay()));
        }
        $this->_client = new Client(['handler' => $handler]);
        $requests = $this->generateRequest();
        $poolConfig = [
            'concurrency' => $this->_concurrency,
            'fulfilled'   => function ($response, $index) {
                $this->taskSuccess($response, $index);
            },
            'rejected'    => function ($reason, $index) {
                $this->taskFail($reason, $index);
            }
        ];
        $pool = new Pool($this->_client, $requests, $poolConfig);
        // Initiate the transfers and create a promise
        $promise = $pool->promise();
        // Force the pool of requests to complete.
        $promise->wait();
    }

    /**
     * 构建请求
     *
     * @param HttpRequestBuilderInterface $requester    请求构建器
     * @return \GuzzleHttp\Promise\PromiseInterface
     */
    protected function bulidRequest(HttpRequestBuilderInterface $requester)
    {
        HttpRequestBuilderInterface::METHOD_GET == $requester->method && $requester->url = $this->generateHttpQueryUrl($requester->url, $requester->params); // GET
        return $this->_client->postAsync($requester->url, $requester());
        // if (HttpRequestBuilderInterface::METHOD_GET == $requester->method) {
        //     $apiUrl = $this->generateHttpQueryUrl($requester->url, $requester->params);
        //     return $this->_client->getAsync($apiUrl, $requester()); // GET
        // } else {
        //     return $this->_client->postAsync($requester->url, $requester()); // POST
        // }
    }

    /**
     * 生成请求
     *
     * @return void
     */
    private function generateRequest()
    {
        foreach ($this->_requestList as $request) {
            yield $request->uid => function () use ($request) {
                return $this->bulidRequest($request);
            };
        }
    }

    /**
     * 任务成功
     *
     * @param Response $response            响应
     * @param string $key                   key
     * @return void
     */
    private function taskSuccess(Response $response, $key)
    {
        $content  = $response->getBody()->getContents();
        $httpCode = $response->getStatusCode();
        $request  = $this->_requestList[$key] ?? null;
        $this->_resultList[$key]             = static::RESULT_FORMAT;
        $this->_resultList[$key]['uid']      = $key;
        $this->_resultList[$key]['url']      = $request->url ?? '';
        $this->_resultList[$key]['httpCode'] = $httpCode;
        $this->_resultList[$key]['content']  = $content;
        // $this->_resultList[$key] = [
        //     'uid'      => $key,
        //     'url'      => $request->url ?? '',
        //     'httpCode' => $httpCode,
        //     'content'  => $content
        // ];
    }

    /**
     * 任务失败
     *
     * @param RequestException $reason      请求异常
     * @param string $key                   key
     * @return void
     */
    private function taskFail(RequestException $reason, $key)
    {
        $response = $reason->getResponse();
        // $content  = $response->getBody()->getContents();
        $content  = method_exists($response, 'getBody') ? $response->getBody()->getContents() : '';
        $httpCode = method_exists($response, 'getStatusCode') ? $response->getStatusCode() : 504; // 504 Gateway Timeout
        $request  = $this->_requestList[$key] ?? null;
        $this->_failedList[$key]             = static::RESULT_FORMAT;
        $this->_failedList[$key]['uid']      = $key;
        $this->_failedList[$key]['url']      = $request->url ?? '';
        $this->_failedList[$key]['httpCode'] = $httpCode;
        $this->_failedList[$key]['content']  = $content;
        // $this->_failedList[$key] = [
        //     'uid'      => $key,
        //     'url'      => $request->url ?? '',
        //     'httpCode' => $httpCode,
        //     'content'  => $content
        // ];
    }
}
