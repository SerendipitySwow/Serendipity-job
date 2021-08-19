<?php
/**
 * This file is part of Serendipity Job
 * @license  https://github.com/serendipitySwow/Serendipity-job/blob/main/LICENSE
 */

declare(strict_types=1);

namespace SerendipityTest\Cases;

use GuzzleHttp\Client as GuzzleHttpClient;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Serendipity\Job\Job\SimpleJob;
use Swow\Coroutine;

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
        $response = $this->client->post('/task/create', [
            \GuzzleHttp\RequestOptions::HEADERS => [
                'nonce' => 'nm33An1FX22SAjZK',
                'signature' => 'ZjUyZTE3M2RkMmUyYzY5MTEyYzRhNTFiZmFkOWFjYjZlZGZhMmJkZGUwZTRjYmViZjVkMWExNmQ4M2FiYjQ0Yw==',
                'app_key' => 'E3F5l1uKFcXUe1gd',
                'payload' => '57bcd60eefcac9701fd2407080a5a7b0',
                'timestamps' => '1627371056',
            ],
            \GuzzleHttp\RequestOptions::JSON => [
                'taskNo' => 'taskNo84',
                'content' => [
                    'class' => '\\Serendipity\\Job\\Job\\SimpleJob',
                    '_params' => [
                        'startDate' => '2021-07-27 17:50:50',
                        'endDate' => '2021-07-27 17:50:50',
                    ],
                ],
                'timeout' => 600000,
                'name' => 'SimpleJob',
                'runtime' => '2021-07-27 17:50:50',
            ],
        ]);
        $this->assertInstanceOf(ResponseInterface::class, $response);
        $this->assertJsonStringEqualsJsonString(
            (string) $response->getBody(),
            json_encode([
                'code' => 1,
                'msg' => '请勿重复提交!',
                'data' => [],
            ], JSON_THROW_ON_ERROR)
        );
        $this->assertJsonStringNotEqualsJsonString(
            (string) $response->getBody(),
            json_encode([
                'code' => 0,
                'msg' => 'success',
                'data' => [
                    'taskID' => 82,
                ],
            ], JSON_THROW_ON_ERROR)
        );
    }

    public function testStress(): void
    {
        for ($i = 0; $i < 50; $i++) {
            Coroutine::run(function () {
                $client = new GuzzleHttpClient();
                $response = $client->post('/task/create', [
                    'base_uri' => 'http://127.0.0.1:9502',
                    \GuzzleHttp\RequestOptions::HEADERS => [
                        'nonce' => 'nm33An1FX22SAjZK',
                        'signature' => 'ZjUyZTE3M2RkMmUyYzY5MTEyYzRhNTFiZmFkOWFjYjZlZGZhMmJkZGUwZTRjYmViZjVkMWExNmQ4M2FiYjQ0Yw==',
                        'app_key' => 'E3F5l1uKFcXUe1gd',
                        'payload' => '57bcd60eefcac9701fd2407080a5a7b0',
                        'timestamps' => '1627371056',
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
                $this->assertInstanceOf(ResponseInterface::class, $response);
//                $this->assertJsonStringEqualsJsonString((string) $response->getBody(),
//                                                        json_encode([
//                                                                        'code' => 1,
//                                                                        'msg' => '请勿重复提交!',
//                                                                        'data' => [],
//                                                                    ], JSON_THROW_ON_ERROR)
//                );
//                $this->assertJsonStringNotEqualsJsonString((string) $response->getBody(),
//                                                           json_encode([
//                                                                           'code' => 0,
//                                                                           'msg' => 'success',
//                                                                           'data' => [
//                                                                               'taskID' => 82,
//                                                                           ],
//                                                                       ], JSON_THROW_ON_ERROR)
//                );
            });
        }
        $this->assertIsInt($i);
    }
}
