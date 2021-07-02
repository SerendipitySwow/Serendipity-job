<?php
/**
 * This file is part of Serendipity Job
 * @license  https://github.com/Hyperf-Glory/Serendipity-job/blob/main/LICENSE
 */

declare(strict_types=1);

namespace Serendipity\Job\Subscriber;

use JetBrains\PhpStorm\ArrayShape;
use Serendipity\Job\Contract\ConfigInterface;
use Serendipity\Job\Contract\StdoutLoggerInterface;
use Serendipity\Job\Crontab\Crontab;
use Serendipity\Job\Crontab\CrontabManager;
use Serendipity\Job\Event\CrontabEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CrontabRegisterSubscriber implements EventSubscriberInterface
{
    protected CrontabManager $crontabManager;

    protected StdoutLoggerInterface $logger;

    private ConfigInterface $config;

    public function __construct(CrontabManager $crontabManager, StdoutLoggerInterface $logger, ConfigInterface $config)
    {
        $this->crontabManager = $crontabManager;
        $this->logger = $logger;
        $this->config = $config;
    }

    #[ArrayShape([CrontabEvent::CRONTAB_REGISTER => 'string'])]
    public static function getSubscribedEvents(): array
    {
        return [
            CrontabEvent::CRONTAB_REGISTER => 'register',
        ];
    }

    public function register(CrontabEvent $event): void
    {
        $crontabs = $this->parseCrontabs();
        foreach ($crontabs as $crontab) {
            if ($crontab instanceof Crontab && $this->crontabManager->register($crontab)) {
                $this->logger->debug(sprintf('Crontab %s have been registered.', $crontab->getName()));
            }
        }
    }

    private function parseCrontabs(): array
    {
        $configCrontabs = $this->config->get('crontab.crontab', []);
        $crontabs = [];
        foreach ($configCrontabs as $crontab) {
            if ($crontab instanceof Crontab) {
                $crontabs[$crontab->getName()] = $crontab;
            }
        }

        return array_values($crontabs);
    }
}
