<?php
/**
 * This file is part of Swow-Cloud/Job
 * @license  https://github.com/serendipity-swow/serendipity-job/blob/main/LICENSE
 */

declare(strict_types=1);

namespace SwowCloud\Job\Kernel\Consul;

use Psr\Container\ContainerInterface;
use SwowCloud\Contract\StdoutLoggerInterface;

class RegisterServices
{
    protected array $services = [];

    private StdoutLoggerInterface $logger;

    protected array $defaultLoggerContext = ['component' => 'consul'];

    /**
     * @var mixed|\SwowCloud\Job\Kernel\Consul\ConsulAgent
     */
    private mixed $consulAgent;

    private string $serviceId = '';

    public function __construct(ContainerInterface $container)
    {
        $this->consulAgent = $container->get(ConsulAgent::class);
        $this->logger = $container->get(StdoutLoggerInterface::class);
    }

    public function register(string $address, int $port, array $service, string $serviceName, string $path, string $serviceId = ''): void
    {
        $this->logger->debug(sprintf('Service %s[%s] is registering to the consul.', $serviceName, $path), $this->defaultLoggerContext);
        if ($this->isRegistered($serviceName, $address, $port, $service['protocol'])) {
            $this->logger->info(sprintf('Service %s[%s] has been already registered to the consul.', $serviceName, $path), $this->defaultLoggerContext);

            return;
        }
        $this->serviceId = $serviceId !== '' ? $serviceId : $this->generateId($this->getLastServiceId($serviceName));
        $requestBody = [
            'Name' => $serviceName,
            'ID' => $this->serviceId,
            'Address' => $address,
            'Port' => $port,
            'Meta' => [
                'Protocol' => $service['protocol'] ?? 'http',
            ],
        ];
        if ($service['protocol'] === 'http') {
            /* @noinspection HttpUrlsUsage */
            $requestBody['Check'] = [
                'HTTP' => "http://{$address}:{$port}/Health",
                'Interval' => '1s',
            ];
        }
        $response = $this->consulAgent->registerService($requestBody);
        if ($response->getStatusCode() === 200) {
            $this->services[$serviceName][$service['protocol']][$address][$port] = true;
            $this->logger->info(sprintf('Service %s[%s]:%s register to the consul successfully.', $serviceName, $path, $serviceId), $this->defaultLoggerContext);
        } else {
            $this->logger->warning(sprintf('Service %s register to the consul failed.', $serviceName), $this->defaultLoggerContext);
        }
    }

    protected function isRegistered(string $name, string $address, int $port, string $protocol): bool
    {
        if (isset($this->services[$name][$protocol][$address][$port])) {
            return true;
        }
        $response = $this->consulAgent->services();
        if ($response->getStatusCode() !== 200) {
            $this->logger->warning(sprintf('Service %s register to the consul failed.', $name), $this->defaultLoggerContext);

            return false;
        }
        $services = $response->json();
        $glue = ',';
        $tag = implode($glue, [$name, $address, $port, $protocol]);
        foreach ($services as $serviceId => $service) {
            if (!isset($service['Service'], $service['Address'], $service['Port'], $service['Meta']['Protocol'])) {
                continue;
            }
            $currentTag = implode($glue, [
                $service['Service'],
                $service['Address'],
                $service['Port'],
                $service['Meta']['Protocol'],
            ]);
            if ($currentTag === $tag) {
                $this->services[$name][$protocol][$address][$port] = true;
                $this->serviceId = $serviceId;

                return true;
            }
        }

        return false;
    }

    protected function generateId(string $name): string
    {
        $exploded = explode('-', $name);
        $length = count($exploded);
        $end = -1;
        if ($length > 1 && is_numeric($exploded[$length - 1])) {
            $end = $exploded[$length - 1];
            unset($exploded[$length - 1]);
        }
        $end = (int) $end;
        ++$end;
        $exploded[] = $end;

        return implode('-', $exploded);
    }

    protected function getLastServiceId(string $name)
    {
        $maxId = -1;
        $lastService = $name;
        $services = $this->consulAgent->services()->json();
        foreach ($services ?? [] as $id => $service) {
            if (isset($service['Service']) && $service['Service'] === $name) {
                $exploded = explode('-', (string) $id);
                $length = count($exploded);
                if ($length > 1 && is_numeric($exploded[$length - 1]) && $maxId < $exploded[$length - 1]) {
                    $maxId = $exploded[$length - 1];
                    $lastService = $service;
                }
            }
        }

        return $lastService['ID'] ?? $name;
    }

    public function deregister(string $serviceId): void
    {
        $this->consulAgent->deregisterService($serviceId);
    }

    public function getServiceId(): string
    {
        return $this->serviceId;
    }
}
