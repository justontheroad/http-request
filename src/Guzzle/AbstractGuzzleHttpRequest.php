<?php

namespace justontheroad\HttpRequest\Guzzle;

use justontheroad\HttpRequest\{
    HttpRetryInterface,
    HttpRequestBuilderInterface
};

/**
 * 抽象Guzzle HTTP请求
 */
abstract class AbstractGuzzleHttpRequest
{
    /**
     * 客户端
     *
     * @var \GuzzleHttp\Client
     */
    protected $_client;
    /**
     * 请求列表
     *
     * @var array
     */
    protected $_requestList;
    /**
     * 结果列表
     *
     * @var array
     */
    protected $_resultList;
    /**
     * 失败列表
     *
     * @var array
     */
    protected $_failedList;
    /**
     * 重设对象
     *
     * @var HttpRetryInterface
     */
    protected $_retry;
    /**
     * 时间线
     *
     * @var array
     */
    private $_timeline;

    /**
     * 获取结果
     *
     * @return array $resultList,$failedList
     */
    public function getResult()
    {
        return [$this->_resultList, $this->_failedList];
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
     * 有设置重试
     *
     * @return boolean  是否设置了重设
     */
    protected function hasSetRetry()
    {
        return $this->_retry instanceof HttpRetryInterface ? true : false;
    }

    /**
     * 记录时间
     *
     * @param boolean $start        trur：开始记录，false：结束记录
     * @param boolean $toString     转字符串
     * @return array|string         ['start' => $startTime, 'end' => $endTime]，或时间记录文本                
     */
    public function recordTime(bool $start = true, bool $toString = false)
    {
        list($msec, $sec) = explode(' ', microtime());
        $msectime = (float)sprintf('%.0f', (floatval($msec) + floatval($sec)) * 1000);
        if ($start) {
            $this->_timeline['start'] = $msectime;
            $this->_timeline['end']   = 0;
        } else {
            empty($this->_timeline['start']) && $this->_timeline['start'] = $msectime;
            $this->_timeline['end'] = $msectime;
        }

        if ($toString) {
            return sprintf(
                '开始时间：%d，结束时间：%d，耗时：%d毫秒',
                $this->_timeline['start'],
                $this->_timeline['end'],
                $this->_timeline['end'] - $this->_timeline['start']
            );
        }
        return $this->_timeline;
    }

    /**
     * 生成完整GET请求URL
     *
     * @param string $url   请求URL
     * @param array $params 参数
     * @return string       网站的URL
     */
    protected function generateHttpQueryUrl(string $url, array $params)
    {
        if (!empty($params) && is_array($params)) {
            $url .= (strpos($url, '?') === false) ? '?' : '&';
            $url .= http_build_query($params);
        }
        return $url;
    }

    /**
     * 构建请求
     *
     * @param HttpRequestBuilderInterface $requester    HTTP请求构建器
     * @return void
     */
    protected abstract function bulidRequest(HttpRequestBuilderInterface $requester);
}
