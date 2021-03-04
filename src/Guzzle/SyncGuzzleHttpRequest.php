<?php

namespace justontheroad\HttpRequest\Guzzle;

use justontheroad\HttpRequest\{
    HttpRequestInterface,
    HttpRequestBuilderInterface
};
use GuzzleHttp\{
    // Pool,
    Client,
    // Psr7\Request,
    // Psr7\Response,
    HandlerStack,
    // Handler\CurlHandler,
    // RequestOptions,
    Middleware
    // Exception\RequestException
};

/**
 * 同步阻塞Guzzle HTTP请求
 * 
 * @method string appendRequest()，用于设置请求
 * @method void request()，执行请求
 * @method array getResult()，获取结果，@return array $resultList[$uid, $url, $httpCode, $content],$failedList
 */
class SyncGuzzleHttpRequest extends AbstractGuzzleHttpRequest implements HttpRequestInterface
{
    /**
     * 设置请求
     *
     * @param string $url       url
     * @param array $params     参数
     * @param array $header     头信息
     * @return string           $uid
     */
    // public function setRequest(string $url, string $method, array $params = [], array $header = [], int $timeout = 0, int $cuid = 0)
    // {
    //     $request = new GuzzleHttpRequestBuilder($url, $method, $params, $header);
    //     $request->timeout = $timeout;
    //     $this->_requestList[$request->uid] = $request;
    //     return $request->uid;
    // }

    /**
     * 设置请求
     *
     * @param HttpRequestBuilderInterface $request  请求 
     * @return string                               $uid
     */
    public function setRequest(HttpRequestBuilderInterface $request)
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
        $handler = HandlerStack::create();
        // 创建重试中间件，指定决策者，指定重试延迟
        if ($this->hasSetRetry()) {
            $handler->push(Middleware::retry($this->_retry->retryDecider(), $this->_retry->retryDelay()));
        }
        $this->_client = new Client(['handler' => $handler]);

        $uid      = key($this->_requestList);
        $key      = $uid;
        $request  = $this->_requestList[$uid] ?? null;
        $response = $this->bulidRequest($request);
        $content  = $response->getBody()->getContents();
        $httpCode = $response->getStatusCode();

        $this->_resultList[$key]             = static::RESULT_FORMAT;
        $this->_resultList[$key]['uid']      = $key;
        $this->_resultList[$key]['url']      = $request->url ?? '';
        $this->_resultList[$key]['httpCode'] = $httpCode;
        $this->_resultList[$key]['content']  = $content;
        // $this->_resultList[$uid] = [
        //     'uid'      => $uid,
        //     'url'      => $request->url ?? '',
        //     'httpCode' => $httpCode,
        //     'content'  => $content
        // ];
    }

    /**
     * 构建请求
     *
     * @param HttpRequestBuilderInterface $requester    请求构建器
     * @return \GuzzleHttp\Psr7\Response
     */
    protected function bulidRequest(HttpRequestBuilderInterface $requester)
    {
        HttpRequestBuilderInterface::METHOD_GET == $requester->method && $requester->url = $this->generateHttpQueryUrl($requester->url, $requester->params); // GET
        return $this->_client->request($requester->method, $requester->url, $requester());
        // if (HttpRequestBuilderInterface::METHOD_GET == $requester->method) {
        //     $apiUrl = $this->generateHttpQueryUrl($requester->url, $requester->params);
        //     return $this->_client->request(HttpRequestBuilderInterface::METHOD_GET, $apiUrl, $requester()); // GET
        // } else {
        //     return $this->_client->request(HttpRequestBuilderInterface::METHOD_POST, $requester->url, $requester()); // POST
        // }
    }
}
