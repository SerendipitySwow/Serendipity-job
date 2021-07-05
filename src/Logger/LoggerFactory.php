<?php
/**
 * This file is part of Serendipity Job
 * @license  https://github.com/serendipitySwow/Serendipity-job/blob/main/LICENSE
 */

declare(strict_types=1);

namespace Serendipity\Job\Logger;

use JetBrains\PhpStorm\ArrayShape;
use Monolog\Formatter\FormatterInterface;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\FormattableHandlerInterface;
use Monolog\Handler\HandlerInterface;
use Monolog\Handler\StreamHandler;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Serendipity\Job\Contract\ConfigInterface;
use Serendipity\Job\Logger\Exception\InvalidConfigException;
use Serendipity\Job\Util\Arr;

class LoggerFactory
{
    protected ContainerInterface $container;

    /**
     * @var ConfigInterface
     */
    protected mixed $config;

    protected array $loggers;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->config = $container->get(ConfigInterface::class);
    }

    public function make($name = 'serendipity', $group = 'default'): LoggerInterface
    {
        $config = $this->config->get('logger');
        if (!isset($config[$group])) {
            throw new InvalidConfigException(sprintf('Logger config[%s] is not defined.', $name));
        }

        $config = $config[$group];
        $handlers = $this->handlers($config);
        $processors = $this->processors($config);

        return make(Logger::class, [
            'name' => $name,
            'handlers' => $handlers,
            'processors' => $processors,
        ]);
    }

    public function get($name = 'serendipity', $group = 'default'): LoggerInterface
    {
        if (isset($this->loggers[$group][$name]) && $this->loggers[$group][$name] instanceof Logger) {
            return $this->loggers[$group][$name];
        }

        return $this->loggers[$group][$name] = $this->make($name, $group);
    }

    #[ArrayShape(['class' => 'array|\ArrayAccess|mixed', 'constructor' => 'array|\ArrayAccess|mixed'])]
    protected function getDefaultFormatterConfig(
        $config
    ): array {
        $formatterClass = Arr::get($config, 'formatter.class', LineFormatter::class);
        $formatterConstructor = Arr::get($config, 'formatter.constructor', []);

        return [
            'class' => $formatterClass,
            'constructor' => $formatterConstructor,
        ];
    }

    #[ArrayShape(['class' => 'array|\ArrayAccess|mixed', 'constructor' => 'array|\ArrayAccess|mixed'])]
    protected function getDefaultHandlerConfig(
        $config
    ): array {
        $handlerClass = Arr::get($config, 'handler.class', StreamHandler::class);
        $handlerConstructor = Arr::get($config, 'handler.constructor', [
            'stream' => BASE_PATH . '/runtime/logs/hyperf.log',
            'level' => Logger::DEBUG,
        ]);

        return [
            'class' => $handlerClass,
            'constructor' => $handlerConstructor,
        ];
    }

    protected function processors(array $config): array
    {
        $result = [];
        if (!isset($config['processors']) && isset($config['processor'])) {
            $config['processors'] = [$config['processor']];
        }

        foreach ($config['processors'] ?? [] as $value) {
            if (is_array($value) && isset($value['class'])) {
                $value = make($value['class'], $value['constructor'] ?? []);
            }

            $result[] = $value;
        }

        return $result;
    }

    protected function handlers(array $config): array
    {
        $handlerConfigs = $config['handlers'] ?? [[]];
        $handlers = [];
        $defaultHandlerConfig = $this->getDefaultHandlerConfig($config);
        $defaultFormatterConfig = $this->getDefaultFormatterConfig($config);
        foreach ($handlerConfigs as $value) {
            $class = $value['class'] ?? $defaultHandlerConfig['class'];
            $constructor = $value['constructor'] ?? $defaultHandlerConfig['constructor'];
            if (isset($value['formatter']) && !isset($value['formatter']['constructor'])) {
                $value['formatter']['constructor'] = $defaultFormatterConfig['constructor'];
            }
            $formatterConfig = $value['formatter'] ?? $defaultFormatterConfig;

            $handlers[] = $this->handler($class, $constructor, $formatterConfig);
        }

        return $handlers;
    }

    protected function handler($class, $constructor, $formatterConfig): HandlerInterface
    {
        /** @var HandlerInterface $handler */
        $handler = make($class, $constructor);

        if ($handler instanceof FormattableHandlerInterface) {
            $formatterClass = $formatterConfig['class'];
            $formatterConstructor = $formatterConfig['constructor'];

            /** @var FormatterInterface $formatter */
            $formatter = make($formatterClass, $formatterConstructor);

            $handler->setFormatter($formatter);
        }

        return $handler;
    }
}
