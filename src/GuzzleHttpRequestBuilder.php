<?php

namespace justontheroad\HttpRequest;

use GuzzleHttp\{
    RequestOptions,
    Cookie\CookieJar
};

/**
 * GuzzleHTTP请求构建器
 * 
 * 使用uid区分每个请求
 * 
 * uid生成规则，hash('sha256', serialize($data))，$data由 $url、$params、$headers、$cuid组成
 * ```php
 *    protected function generateUid() {
 *      $data = [
 *          'url'     => $this->url,
 *          'params'  => $this->params,
 *          'headers' => $this->headers
 *      ];
 *      0 != $this->cuid && $data['cuid'] = $this->cuid;
 *      return hash('sha256', serialize($data));
 *    }
 * ```
 * 
 * @property const METHOD_GET GET 请求
 * @property const METHOD_POST POST 请求
 * @property const METHOD_PUT PUT 请求
 * @property const DEFAULT_TIMEOUT 默认超时时间 3秒
 * @property string $url url
 * @property string $method 请求方法
 * @property array $params 请求参数
 * @property array $headers 头信息
 * @property string $uid 唯一id
 * @property integer $timeout 超时时间，默认0，不超时
 * @property string $cuid 用户自定义uid
 */
class GuzzleHttpRequestBuilder implements HttpRequestBuilderInterface
{
    /**
     * url
     *
     * @var string
     */
    public $url;
    /**
     * 方法 GET POST
     *
     * @var string
     */
    public $method;
    /**
     * 请求参数
     *
     * @var array
     */
    public $params;
    /**
     * 头信息
     *
     * @var array
     */
    public $headers;
    /**
     * 唯一id
     *
     * @var string
     */
    public $uid;
    /**
     * 超时时间，默认0，不超时
     *
     * @var integer
     */
    public $timeout;
    /**
     * 用户自定义uid
     * 当生成的uid完全一致时，可以增加cuid用于区分
     * 
     * @var string
     */
    public $cuid;
    /**
     * 请求选项
     *
     * @var array
     */
    protected $_options;
    /**
     * 转json请求
     *
     * @var boolean
     */
    protected $_toJson;

    /**
     * HTTP请求构建器
     *
     * @param string $url       url
     * @param string $method    请求方法
     * @param mixed $params     请求参数
     * @param array $headers    头信息
     * @param integer $timeout  超时
     * @param integer $cuid     用户自定义uid
     */
    public function __construct(string $url, string $method = self::METHOD_GET,  $params = [], array $headers = [], int $timeout = 0, int $cuid = 0)
    {
        $this->url      = $url;
        $this->method   = $method;
        $this->params   = $params;
        $this->headers  = $headers;
        $this->timeout  = $timeout;
        $this->cuid     = $cuid;
        $this->uid      = $this->generateUid(); // 生成唯一id

        $this->_options = [];
        $this->_toJson  = false;
    }

    /**
     * 设置请求选项
     *
     * @param array $options    请求选项
     * @return static
     */
    public function setOptions(array $options)
    {
        $this->_options = $options;
        return $this;
    }

    /**
     * 转json请求
     *
     * @return static
     */
    public function toJson()
    {
        $this->_toJson = true;
        return $this;
    }

    /**
     * 生成预发布选项
     *
     * @return array        预发布cookie，['cookies' => $cookies]，$cookies包含['staging' => 'true']
     */
    public function generateStagingOption(): array
    {
        $options   = [];
        $apiDomain = parse_url($this->url, PHP_URL_HOST);
        $options[RequestOptions::COOKIES] = CookieJar::fromArray(
            ['staging' => 'true'],
            mb_substr($apiDomain, stripos($apiDomain, '.'))
        );

        return $options;
    }

    /**
     * @inheritDoc
     * 
     */
    public function build()
    {
        $data = [
            'url'     => $this->url,
            'params'  => $this->params,
            'headers' => $this->headers
        ];

        return $data;
    }

    /**
     * @inheritDoc
     *
     */
    public function buildOptions()
    {
        $options = [
            // RequestOptions::VERIFY      => false,
            RequestOptions::HEADERS     => $this->headers
        ];
        // self::METHOD_POST == $this->method && $options[RequestOptions::FORM_PARAMS] = $this->params;
        // self::METHOD_JSON == $this->method && $options[RequestOptions::JSON] = $this->params;
        if (self::METHOD_POST == $this->method && $this->_toJson) {
            $options[RequestOptions::JSON] = $this->params;
        } else if (self::METHOD_POST == $this->method) {
            $options[RequestOptions::FORM_PARAMS] = $this->params;
        }

        0 < ($this->timeout) && $options[RequestOptions::TIMEOUT] = $this->timeout;
        // !empty($this->_options) && $options = array_merge($options, $this->_options);
        if (!empty($this->_options)) {
            foreach ($this->_options as $option => $val) {
                $options[$option] = $val;
            }
        }
        // if ($this->_isStaging) {
        //     $apiDomain = parse_url($this->url, PHP_URL_HOST);
        //     $options[RequestOptions::COOKIES] = CookieJar::fromArray(
        //         ['staging' => 'true'],
        //         mb_substr($apiDomain, stripos($apiDomain, '.'))
        //     );
        // }

        return $options;
    }

    public function __invoke(array $otherOptions = [])
    {
        return $this->buildOptions($otherOptions);
    }

    /**
     * 生成uid
     *
     * @return string       uid
     */
    protected function generateUid(): string
    {
        // $data = [
        //     'url'     => $this->url,
        //     'params'  => $this->params,
        //     'headers' => $this->headers
        // ];
        $data = $this->build();
        0 != $this->cuid && $data['cuid'] = $this->cuid;

        return hash('sha256', serialize($data));
    }
}
