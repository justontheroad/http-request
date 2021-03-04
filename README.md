# HTTP Request Component
## composer 安装本项目
1. 命令行执行安装
    ```
    composer require justontheroad/http-request
    ```

##  使用示例
    ```
    $options = [
            \GuzzleHttp\RequestOptions::ALLOW_REDIRECTS => false
        ]; // 禁止重定向
    $header  = [];
    $client  = GuzzleHttpRequest::create();

    $client->async(); // 异步请求，默认为同步请求，调用async方法切换为异步
    $client->setRetry(new GuzzleHttpRequestRetry(2, 1000)); // 重试机制，重试2次，中间间隔1000ms
    $client->setCache(new HttpRequestRedisCache(Redis::getInstance()->getRedisOperateObj(), 'base:http_request_api:', 60, 6)); // 请求缓存，redis策略，数据过期时间60s，空数据过期6s
    $client
        ->appendRequest(
            $requester1 = new GuzzleHttpRequestBuilder(
                'http://base.glosopxx.com/api/pipeline/items', // 测试404
                GuzzleHttpRequestBuilder::METHOD_POST,
                [
                    'site'   => 'ZF',
                    'apiId' => '81194069E82FFDFE',
                    'token' => 'acc4fcecd5bc0e46b1849aedb69ccf38'
                ],
                $header,
                2
            )
        )
        ->appendRequest(
            $requester2 = new GuzzleHttpRequestBuilder(
                'http://base.glosop.com/api/pipeline/items',
                GuzzleHttpRequestBuilder::METHOD_POST,
                [
                    'site'   => 'ZF',
                    'apiId' => '81194069E82FFDFE',
                    'token' => 'acc4fcecd5bc0e46b1849aedb69ccf38'
                ],
                $header,
                3
            )
        )
        ->appendRequest(
            $requester3 = new GuzzleHttpRequestBuilder(
                'http://base.glosop.com/api/category/items',
                GuzzleHttpRequestBuilder::METHOD_POST,
                [
                    'site'   => 'ZF',
                    'apiId' => '81194069E82FFDFE',
                    'token' => 'acc4fcecd5bc0e46b1849aedb69ccf38'
                ],
                $header
            )
        )
        ->appendRequest(
            $requester4 = new GuzzleHttpRequestBuilder(
                'http://base.glosop.com/category/api/goods-shop-api/calculate',
                GuzzleHttpRequestBuilder::METHOD_POST,
                [
                    'site' => 'ZF',
                    'apiId' => '81194069E82FFDFE',
                    'token' => '123',
                    'pipelineId' => '1',
                    'goodsList'  => '[{"key":"goodsList","value":"[{\"goods_id\":\"566352\",\"cateid\":\"1\",\"goods_sn\":\"205842606\",\"chuhuo_price\":\"43\",\"goods_volume_weight\":\"10\",\"is_free_shipping\":\"1\"},{\"goods_id\":\"5879\",\"cateid\":\"54\",\"goods_sn\":\"113892505\",\"chuhuo_price\":\"103\",\"goods_volume_weight\":\"0.650\",\"is_free_shipping\":\"1\"}]","description":"","type":"text","enabled":true}]',
                    'goodsSnArr' => '257041803,257041802'
                ],
                $header
            )
        );
    
    $requester1->setOptions($options);
    $requester2->setOptions($options);
    $requester3->setOptions($options);
    $requester4->setOptions($options);

    try {
        $client->request();
        list($resultList, $failedList) = $client->getResult();

        return json_encode(['success' => $resultList, 'failed' => $failedList]);
    } catch (\Exception $e) {
        return $e;
    }
    ```