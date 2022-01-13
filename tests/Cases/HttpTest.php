<?php
/**
 * This file is part of Swow-Cloud/Job
 * @license  https://github.com/serendipity-swow/serendipity-job/blob/main/LICENSE
 */

declare(strict_types=1);

namespace SwowCloud\JobTest\Cases;

use GuzzleHttp\Client as GuzzleHttpClient;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Swow\Coroutine;
use SwowCloud\Job\Job\SimpleJob;
use SwowCloud\Job\Logger\LoggerFactory;

/**
 * @internal
 * @coversNothing
 */
class HttpTest extends TestCase
{
    protected GuzzleHttpClient $client;

    public function __construct(?string $name = null, array $data = [], $dataName = '')
    {
        $this->client = new GuzzleHttpClient([
            'base_uri' => 'http://127.0.0.1:9502',
        ]);
        parent::__construct($name, $data, $dataName);
    }

    public function testTaskCreate()
    {
        /*
        $response = $this->client->post('/task/create', [
            \GuzzleHttp\RequestOptions::HEADERS => [
                'nonce'      => 'nm33An1FX22SAjZK',
                'signature'  => 'ZjUyZTE3M2RkMmUyYzY5MTEyYzRhNTFiZmFkOWFjYjZlZGZhMmJkZGUwZTRjYmViZjVkMWExNmQ4M2FiYjQ0Yw==',
                'app_key'    => 'E3F5l1uKFcXUe1gd',
                'payload'    => '57bcd60eefcac9701fd2407080a5a7b0',
                'timestamps' => '1627371056',
            ],
            \GuzzleHttp\RequestOptions::JSON    => [
                'taskNo'  => 'taskNo84',
                'content' => [
                    'class'   => '\\SwowCloud\\Job\\Job\\SimpleJob',
                    '_params' => [
                        'startDate' => '2021-07-27 17:50:50',
                        'endDate'   => '2021-07-27 17:50:50',
                    ],
                ],
                'timeout' => 600000,
                'name'    => 'SimpleJob',
                'runtime' => '2021-07-27 17:50:50',
            ],
        ]);
        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertJsonStringEqualsJsonString(
            (string)$response->getBody(),
            json_encode([
                'code' => 1,
                'msg'  => '请勿重复提交!',
                'data' => [],
            ], JSON_THROW_ON_ERROR)
        );
        $this->assertJsonStringNotEqualsJsonString(
            (string)$response->getBody(),
            json_encode([
                'code' => 0,
                'msg'  => 'success',
                'data' => [
                    'taskID' => 82,
                ],
            ], JSON_THROW_ON_ERROR)
        );
        */
    }

    public function testStress(): void
    {
        for ($i = 0; $i < 50; $i++) {
            /*
            Coroutine::run(function ()
            {
                $client   = new GuzzleHttpClient();
                $response = $client->post('/task/create', [
                    'base_uri'                          => 'http://127.0.0.1:9502',
                    \GuzzleHttp\RequestOptions::HEADERS => [
                        'nonce'      => 'nm33An1FX22SAjZK',
                        'signature'  => 'ZjUyZTE3M2RkMmUyYzY5MTEyYzRhNTFiZmFkOWFjYjZlZGZhMmJkZGUwZTRjYmViZjVkMWExNmQ4M2FiYjQ0Yw==',
                        'app_key'    => 'E3F5l1uKFcXUe1gd',
                        'payload'    => '57bcd60eefcac9701fd2407080a5a7b0',
                        'timestamps' => '1627371056',
                    ],
                    \GuzzleHttp\RequestOptions::JSON    => [
                        'taskNo'  => 'taskNo' . uniqid('', true),
                        'content' => [
                            'class'   => SimpleJob::class,
                            '_params' => [
                                'startDate' => '2021-07-27 17:50:50',
                                'endDate'   => '2021-07-27 17:50:50',
                            ],
                        ],
                        'timeout' => 6000,
                        'name'    => 'SimpleJob',
                        'runtime' => '2021-07-27 17:50:50',
                    ],
                ]);
                $this->assertInstanceOf(ResponseInterface::class, $response);
                $this->assertJsonStringEqualsJsonString(
                    (string)$response->getBody(),
                    json_encode([
                        'code' => 1,
                        'msg'  => '请勿重复提交!',
                        'data' => [],
                    ], JSON_THROW_ON_ERROR)
                );
                $this->assertJsonStringNotEqualsJsonString(
                    (string)$response->getBody(),
                    json_encode([
                        'code' => 0,
                        'msg'  => 'success',
                        'data' => [
                            'taskID' => 82,
                        ],
                    ], JSON_THROW_ON_ERROR)
                );
            });


            */
            $this->assertIsInt($i);
        }
    }

    /**
     * 压力测试nsq推送
     */
    public function testNsqPublish(): void
    {
        $startTime = microtime(true);
        for ($i = 0; $i < 100; $i++) {
            Coroutine::run(function () {
                $client = new GuzzleHttpClient();
                $response = $client->post('/nsq/publish', [
                    'base_uri' => 'http://127.0.0.1:9502',
                    \GuzzleHttp\RequestOptions::HEADERS => [
                        'nonce' => 'jcpxB9oadf6al6Tv',
                        'timestamps' => 1639044971,
                        'signature' => 'MDcwYmNkZjc2ZThhYmYxNWUwYmNlZWFkNmE1YjU2M2U3MDQxNWNkN2RkN2QyMmVjODhhNDE3MGU5MzEyZTkyNQ==',
                        'app_key' => 'svpC69glRX0eJUqw',
                        'payload' => '57bcd60eefcac9701fd2407080a5a7b0',
                        'secretKey' => 'pOKCTaHwMZaKPk3lfbVYfW07NuFjMAXX',
                    ],
                    \GuzzleHttp\RequestOptions::JSON => [
                        'task_id' => 10,
                    ],
                ]);
                $this->assertJsonStringEqualsJsonString(
                    (string) $response->getBody(),
                    json_encode([
                        'code' => 0,
                        'msg' => 'Ok!',
                        'data' => [],
                    ], JSON_THROW_ON_ERROR)
                );
            });
            $this->assertIsInt($i);
        }
        $endTime = microtime(true);
        $logger = make(LoggerFactory::class)->get();
        //4510.9159946442ms
        $logger->info(($endTime - $startTime) * 1000 . 'ms');
    }

    /**
     * 压力测试创建任务
     */
    public function testAbTaskCreate()
    {
        for ($i = 0; $i < 100; $i++) {
            Coroutine::run(function () {
                $client = new GuzzleHttpClient();
                $response = $client->post('/task/create', [
                    'base_uri' => 'http://127.0.0.1:9502',
                    \GuzzleHttp\RequestOptions::HEADERS => [
                        'nonce' => 'jcpxB9oadf6al6Tv',
                        'timestamps' => 1639044971,
                        'signature' => 'MDcwYmNkZjc2ZThhYmYxNWUwYmNlZWFkNmE1YjU2M2U3MDQxNWNkN2RkN2QyMmVjODhhNDE3MGU5MzEyZTkyNQ==',
                        'app_key' => 'svpC69glRX0eJUqw',
                        'payload' => '57bcd60eefcac9701fd2407080a5a7b0',
                        'secretKey' => 'pOKCTaHwMZaKPk3lfbVYfW07NuFjMAXX',
                    ],
                    \GuzzleHttp\RequestOptions::JSON => [
                        'taskNo' => 'taskNo' . uniqid('', true),
                        'content' => [
                            'class' => SimpleJob::class,
                            '_params' => [
                                'startDate' => '2021-07-27 17:50:50',
                                'endDate' => '2021-07-27 17:50:50',
                            ],
                        ],
                        'timeout' => 6000,
                        'name' => 'SimpleJob',
                        'runtime' => '2021-07-27 17:50:50',
                    ],
                ]);
                $body = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);
                $this->assertArrayHasKey('code', $body);
                $this->assertEquals(0, $body['code']);
            });
            $this->assertIsInt($i);
        }
    }
}
