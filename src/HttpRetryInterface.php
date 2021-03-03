<?php

namespace justontheroad\HttpRequest;

/**
 * HTTP重试接口
 */
interface HttpRetryInterface
{
    /**
     * 重试决策者
     *
     */
    public function retryDecider();
    /**
     * 重试延迟
     *
     */
    public function retryDelay();
}
