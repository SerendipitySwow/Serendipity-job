<?php
/**
 * This file is part of Swow-Cloud/Job
 * @license  https://github.com/serendipity-swow/serendipity-job/blob/master/LICENSE
 */

declare(strict_types=1);

namespace SwowCloud\Job\Dingtalk\Http;

use Exception;
use GuzzleHttp\Exception\GuzzleException;
use Hyperf\Utils\Codec\Json;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use SwowCloud\Job\Dingtalk\ClientInterface;

class Client implements ClientInterface
{
    protected array $options = [];

    protected string $token = '';

    protected array $config = [];

    public function __construct(array $config)
    {
        $this->config = $config;
        $options = $config['options'] ?? [];
        if (empty($config['token'])) {
            throw new InvalidArgumentException('Token cannot be empty');
        }
        $this->token = $config['token'];
        if (!isset($options['base_uri'])) {
            $options['base_uri'] = sprintf('https://%s', 'oapi.dingtalk.com/robot/send');
        }
        $this->options = $options;
    }

    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * @throws GuzzleException
     * @throws JsonException
     */
    public function send(array $params = []): ResponseInterface
    {
        $client = new \GuzzleHttp\Client($this->options);

        return $client->post($this->getRobotUrl(), array_merge($this->options, [
            'body' => Json::encode($params, JSON_THROW_ON_ERROR),
            'headers' => [
                'Content-Type' => 'application/json',
            ],
            'verify' => $this->config['ssl_verify'] ?? true,
        ]));
    }

    protected function getRobotUrl(): string
    {
        $query['access_token'] = $this->token;
        if (isset($this->config['secret']) && $secret = $this->config['secret']) {
            try {
                $timestamp = time() . sprintf('%03d', random_int(1, 999));
                $sign = hash_hmac('sha256', $timestamp . "\n" . $secret, $secret, true);
                $query['timestamp'] = $timestamp;
                $query['sign'] = base64_encode($sign);
            } catch (Exception) {
                // do something
            }
        }

        return $this->options['base_uri'] . '?' . http_build_query($query);
    }
}
