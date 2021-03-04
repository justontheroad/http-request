<?php

namespace app\controllers;

use justontheroad\HttpRequest\{
    Guzzle\BatchAsyncGuzzleHttpRequest,
    Guzzle\SyncGuzzleHttpRequest,
    GuzzleHttpRequestBuilder,
    GuzzleHttpRequestRetry,
    GuzzleHttpRequest,
    HttpRequestRedisCache
};
use yii\web\Controller;
use Yii;

class TestHttpRequestController extends Controller
{
    public function actionAsync(): string
    {
        $async  = new BatchAsyncGuzzleHttpRequest();
        $async->setRetry(new GuzzleHttpRequestRetry());
        // $header = [
        //     'User-Agent'      => 'PostmanRuntime/7.26.5',
        //     'Accept'          => '*/*',
        //     'Accept-Encoding' => 'gzip, deflate, br'  
        // ];
        $header = [];
        $uids   = [];
        $url    = 'http://www.base.com.xx.php7.egomsl.com/api/pipeline/items';
        $uids[$url] = $async->appendRequest(
            new GuzzleHttpRequestBuilder($url, GuzzleHttpRequestBuilder::METHOD_GET, [], $header)
        ); // 测试失败
        $url    = 'http://www.base.com.master.php7.egomsl.com/api/pipeline/items';
        $uids[$url] = $async->appendRequest(
            new GuzzleHttpRequestBuilder($url, GuzzleHttpRequestBuilder::METHOD_POST, [
                'site'   => 'ZF',
                'apiId' => '81194069E82FFDFE',
                'token' => 'acc4fcecd5bc0e46b1849aedb69ccf38'
            ], $header, 3)
        );
        $url    = 'http://www.base.com.master.php7.egomsl.com/api/category/items';
        $uids[$url] = $async->appendRequest(
            new GuzzleHttpRequestBuilder($url, GuzzleHttpRequestBuilder::METHOD_POST, [
                'site'   => 'ZF',
                'apiId' => '81194069E82FFDFE',
                'token' => 'acc4fcecd5bc0e46b1849aedb69ccf38'
            ], $header)
        );
        $url    = 'http://www.base.com.master.php7.egomsl.com/category/api/goods-shop-api/calculate'; // 测试post
        $uids[$url] = $async->appendRequest(
            new GuzzleHttpRequestBuilder($url, GuzzleHttpRequestBuilder::METHOD_POST, [
                'site' => 'ZF',
                'apiId' => '81194069E82FFDFE',
                'token' => '123',
                'pipelineId' => '1',
                'goodsList'  => '[{"key":"goodsList","value":"[{\"goods_id\":\"566352\",\"cateid\":\"1\",\"goods_sn\":\"205842606\",\"chuhuo_price\":\"43\",\"goods_volume_weight\":\"10\",\"is_free_shipping\":\"1\"},{\"goods_id\":\"5879\",\"cateid\":\"54\",\"goods_sn\":\"113892505\",\"chuhuo_price\":\"103\",\"goods_volume_weight\":\"0.650\",\"is_free_shipping\":\"1\"}]","description":"","type":"text","enabled":true}]',
                'goodsSnArr' => '257041803,257041802'
            ], $header)
        );

        try {
            $async->setConcurrency(4)->request();
            list($resultList, $failedList) = $async->getResult();

            return json_encode(['success' => $resultList, 'failed' => $failedList]);
        } catch (\Exception $e) {
            return $e;
        }
    }

    public function actionSync(): string
    {
        $sync   = new SyncGuzzleHttpRequest();
        $sync->setRetry(new GuzzleHttpRequestRetry());
        $header = [];
        $uids   = [];
        $url    = 'http://www.base.com.master.php7.egomsl.com/api/pipeline/items';
        $uids[$url] = $sync->setRequest(
            new GuzzleHttpRequestBuilder($url, GuzzleHttpRequestBuilder::METHOD_POST, [
                'site'   => 'ZF',
                'apiId' => '81194069E82FFDFE',
                'token' => 'acc4fcecd5bc0e46b1849aedb69ccf38'
            ], $header, 3)
        );

        try {
            $sync->request();
            list($resultList, $failedList) = $sync->getResult();

            return json_encode(['success' => $resultList]);
        } catch (\Exception $e) {
            return $e;
        }
    }

    public function actionRetrySync(): string
    {
        $sync   = new SyncGuzzleHttpRequest();
        $sync->setRetry(new GuzzleHttpRequestRetry(2));
        $header = [];
        $uids   = [];
        $url    = 'http://www.base.com.local.php7.egomsl.com/api/pipeline/items';
        $uids[$url] = $sync->setRequest(
            new GuzzleHttpRequestBuilder($url, GuzzleHttpRequestBuilder::METHOD_POST, [
                'site'   => 'ZF',
                'apiId' => '81194069E82FFDFE',
                'token' => 'acc4fcecd5bc0e46b1849aedb69ccf38'
            ], $header, 3)
        );

        try {
            $sync->recordTime(true);
            $sync->request();
            var_dump($sync->recordTime(false, true));
            list($resultList, $failedList) = $sync->getResult();

            return json_encode(['result' => $resultList]);
        } catch (\Exception $e) {
            return $e;
        }
    }

    public function actionRetryAsync(): string
    {
        $options = [
            \GuzzleHttp\RequestOptions::ALLOW_REDIRECTS => false
        ]; // 禁止重定向
        $async  = new BatchAsyncGuzzleHttpRequest();
        $async->setRetry(new GuzzleHttpRequestRetry(2, 1000));
        // $header = [
        //     'User-Agent'      => 'PostmanRuntime/7.26.5',
        //     'Accept'          => '*/*',
        //     'Accept-Encoding' => 'gzip, deflate, br'  
        // ];
        $header = [];
        $uids   = [];
        $url    = 'http://www.base.com.local.php7.egomsl.com/api/pipeline/items';
        $uids[$url] = $async->appendRequest(
            (new GuzzleHttpRequestBuilder($url, GuzzleHttpRequestBuilder::METHOD_POST, [
                'site'   => 'ZF',
                'apiId' => '81194069E82FFDFE',
                'token' => 'acc4fcecd5bc0e46b1849aedb69ccf38'
            ], $header, 3))->setOptions($options)
        );
        $url    = 'http://www.base.com.local.php7.egomsl.com/api/category/items';
        $uids[$url] = $async->appendRequest(
            (new GuzzleHttpRequestBuilder($url, GuzzleHttpRequestBuilder::METHOD_POST, [
                'site'   => 'ZF',
                'apiId' => '81194069E82FFDFE',
                'token' => 'acc4fcecd5bc0e46b1849aedb69ccf38'
            ], $header))->setOptions($options)
        );
        $url    = 'http://www.base.com.local.php7.egomsl.com/category/api/goods-shop-api/calculate'; // 测试post
        $uids[$url] = $async->appendRequest(
            (new GuzzleHttpRequestBuilder($url, GuzzleHttpRequestBuilder::METHOD_POST, [
                'site' => 'ZF',
                'apiId' => '81194069E82FFDFE',
                'token' => '123',
                'pipelineId' => '1',
                'goodsList'  => '[{"key":"goodsList","value":"[{\"goods_id\":\"566352\",\"cateid\":\"1\",\"goods_sn\":\"205842606\",\"chuhuo_price\":\"43\",\"goods_volume_weight\":\"10\",\"is_free_shipping\":\"1\"},{\"goods_id\":\"5879\",\"cateid\":\"54\",\"goods_sn\":\"113892505\",\"chuhuo_price\":\"103\",\"goods_volume_weight\":\"0.650\",\"is_free_shipping\":\"1\"}]","description":"","type":"text","enabled":true}]',
                'goodsSnArr' => '257041803,257041802'
            ], $header))->setOptions($options)
        );

        try {
            $async->setConcurrency(3)->request();
            list($resultList, $failedList) = $async->getResult();

            return json_encode(['success' => $resultList, 'failed' => $failedList]);
        } catch (\Exception $e) {
            return $e;
        }
    }

    public function actionFactory(): string
    {
        $params  = Yii::$app->request->get();
        $async   = $params['async'] ?? false;
        $cache   = $params['cache'] ?? false;
        $retry   = $params['retry'] ?? false;
        $options = [
            \GuzzleHttp\RequestOptions::ALLOW_REDIRECTS => false
        ]; // 禁止重定向
        $header  = [];
        // $request = [
        //     [
        //         'http://www.base.com.local.php7.egomsl.com/api/pipeline/items', 
        //         GuzzleHttpRequestBuilder::METHOD_POST,
        //         [
        //             'site'   => 'ZF',
        //             'apiId' => '81194069E82FFDFE',
        //             'token' => 'acc4fcecd5bc0e46b1849aedb69ccf38'
        //         ], 
        //         $header,
        //         3
        //     ]
        // ]; // test Request Builder 对象有误，必须为HttpRequestBuilder
        // $request = [
        //     new GuzzleHttpRequestBuilder(
        //         'http://www.base.com.xxx.php7.egomsl.com/api/pipeline/items', // 测试404
        //         GuzzleHttpRequestBuilder::METHOD_POST,
        //         [
        //             'site'   => 'ZF',
        //             'apiId' => '81194069E82FFDFE',
        //             'token' => 'acc4fcecd5bc0e46b1849aedb69ccf38'
        //         ],
        //         $header,
        //         2),
        //     new GuzzleHttpRequestBuilder(
        //         'http://www.base.com.xxx.php7.egomsl.com/api/pipeline/items', // 测试404
        //         GuzzleHttpRequestBuilder::METHOD_POST,
        //         [
        //             'site'   => 'ZF',
        //             'apiId' => '81194069E82FFDFE',
        //             'token' => 'acc4fcecd5bc0e46b1849aedb69ccf38'
        //         ],
        //         $header,
        //         2,
        //         3333),
        //     new GuzzleHttpRequestBuilder(
        //         // 'http://www.base.com.local.php7.egomsl.com/api/pipeline/items', // 测试重试
        //         'http://www.base.com.master.php7.egomsl.com/api/pipeline/items',
        //         GuzzleHttpRequestBuilder::METHOD_POST,
        //         [
        //             'site'   => 'ZF',
        //             'apiId' => '81194069E82FFDFE',
        //             'token' => 'acc4fcecd5bc0e46b1849aedb69ccf38'
        //         ],
        //         $header,
        //         3),
        //     new GuzzleHttpRequestBuilder(
        //         // 'http://www.base.com.local.php7.egomsl.com/api/category/items', // 测试重试
        //         'http://www.base.com.master.php7.egomsl.com/api/category/items',
        //         GuzzleHttpRequestBuilder::METHOD_POST,
        //         [
        //             'site'   => 'ZF',
        //             'apiId' => '81194069E82FFDFE',
        //             'token' => 'acc4fcecd5bc0e46b1849aedb69ccf38'
        //         ],
        //         $header),
        //     new GuzzleHttpRequestBuilder(
        //         // 'http://www.base.com.local.php7.egomsl.com/category/api/goods-shop-api/calculate', // 测试重试
        //         'http://www.base.com.master.php7.egomsl.com/category/api/goods-shop-api/calculate',
        //         GuzzleHttpRequestBuilder::METHOD_POST,
        //         [
        //             'site' => 'ZF',
        //             'apiId' => '81194069E82FFDFE',
        //             'token' => '123',
        //             'pipelineId' => '1',
        //             'goodsList'  => '[{"key":"goodsList","value":"[{\"goods_id\":\"566352\",\"cateid\":\"1\",\"goods_sn\":\"205842606\",\"chuhuo_price\":\"43\",\"goods_volume_weight\":\"10\",\"is_free_shipping\":\"1\"},{\"goods_id\":\"5879\",\"cateid\":\"54\",\"goods_sn\":\"113892505\",\"chuhuo_price\":\"103\",\"goods_volume_weight\":\"0.650\",\"is_free_shipping\":\"1\"}]","description":"","type":"text","enabled":true}]',
        //             'goodsSnArr' => '257041803,257041802'
        //         ],
        //         $header),
        // ];
        $client = GuzzleHttpRequest::create();
        $async && $client->async();
        $retry && $client->setRetry(new GuzzleHttpRequestRetry(2, 1000));
        $cache && $client->setCache(new HttpRequestRedisCache('base:http_request_api:', 60, 6));
        $client
            // ->setRetry(new GuzzleHttpRequestRetry(2, 1000))
            // ->setCache(new HttpRequestRedisCache('base:http_request_api:', 60, 6))
            // ->setOptions($options)
            ->appendRequest(
                $requester1 = new GuzzleHttpRequestBuilder(
                    'http://www.base.com.xxx.php7.egomsl.com/api/pipeline/items', // 测试404
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
                    'http://www.base.com.local.php7.egomsl.com/api/pipeline/items', // 测试404
                    GuzzleHttpRequestBuilder::METHOD_POST,
                    [
                        'site'   => 'ZF',
                        'apiId' => '81194069E82FFDFE',
                        'token' => 'acc4fcecd5bc0e46b1849aedb69ccf38'
                    ],
                    $header,
                    2,
                    3333
                )
            )
            ->appendRequest(
                $requester3 = new GuzzleHttpRequestBuilder(
                    'http://www.base.com.master.php7.egomsl.com/api/pipeline/items',
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
                $requester4 = new GuzzleHttpRequestBuilder(
                    'http://www.base.com.master.php7.egomsl.com/api/category/items',
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
                $requester5 = new GuzzleHttpRequestBuilder(
                    'http://www.base.com.master.php7.egomsl.com/category/api/goods-shop-api/calculate',
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
        $requester5->setOptions($options);

        // try {
        $client->request();
        list($resultList, $failedList) = $client->getResult();

        return json_encode(['success' => $resultList, 'failed' => $failedList]);
        // } catch (\Exception $e) {
        //     return $e;
        // }
    }
}
