<?php
/**
 * This file is part of Swow-Cloud/Job
 * @license  https://github.com/serendipity-swow/serendipity-job/blob/main/LICENSE
 */

declare(strict_types=1);

namespace SwowCloud\Job\Kernel\Consul;

use Hyperf\Contract\ConfigInterface;
use JetBrains\PhpStorm\Pure;
use Psr\Container\ContainerInterface;
use SwowCloud\Consul\Agent;
use SwowCloud\Consul\Client;

class ConsulFactory
{
    #[Pure]
    public function __invoke(ContainerInterface $container): Agent
    {
        return new Agent(static function () use ($container) {
            $config = $container->get(ConfigInterface::class);
            $token = $config->get('consul.token', '');
            $options = [
                'timeout' => 2,
                'base_uri' => $config->get('consul.uri', Client::DEFAULT_URI),
            ];

            if (!empty($token)) {
                $options['headers'] = [
                    'X-Consul-Token' => $token,
                ];
            }

            return new \GuzzleHttp\Client($options);
        });
    }
}
