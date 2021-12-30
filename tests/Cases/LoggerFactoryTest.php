<?php
/**
 * This file is part of Swow-Cloud/Job
 * @license  https://github.com/serendipity-swow/serendipity-job/blob/main/LICENSE
 */

declare(strict_types=1);

namespace SwowCloud\JobTest\Cases;

use Psr\Log\LoggerInterface;
use ReflectionClass;
use SwowCloud\Job\Kernel\Logger\AppendRequestIdProcessor;
use SwowCloud\Job\Logger\LoggerFactory;
use SwowCloud\JobTest\HttpTestCase;

/**
 * @internal
 * @coversNothing
 */
class LoggerFactoryTest extends HttpTestCase
{
    protected LoggerInterface $logger;

    public function __construct(?string $name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);
        $this->logger = make(LoggerFactory::class)->get();
    }

    public function testLoggerInstance(): void
    {
        $this->assertInstanceOf(LoggerInterface::class, $this->logger);
    }

    public function testNotSetProcessor()
    {
        $reflectionClass = new ReflectionClass($this->logger);
        $handlersProperty = $reflectionClass->getProperty('processors');
        $handlersProperty->setAccessible(true);
        $processors = $handlersProperty->getValue($this->logger);
        $this->assertNotEmpty($processors);
    }

    public function testProcessor()
    {
        $reflectionClass = new ReflectionClass($this->logger);
        $handlersProperty = $reflectionClass->getProperty('processors');
        $handlersProperty->setAccessible(true);
        $processors = $handlersProperty->getValue($this->logger);
        $this->assertCount(1, $processors);
        $this->assertInstanceOf(AppendRequestIdProcessor::class, $processors[0]);

        $this->logger->info('Hello world.');
    }
}
