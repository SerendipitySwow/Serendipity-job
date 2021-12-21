<?php
/**
 * This file is part of Swow-Cloud/Job
 * @license  https://github.com/serendipity-swow/serendipity-job/blob/main/LICENSE
 */

declare(strict_types=1);

namespace SwowCloud\Job\Subscriber;

use Hyperf\Utils\Arr;
use Hyperf\Utils\Str;
use JetBrains\PhpStorm\ArrayShape;
use Psr\Container\ContainerInterface;
use SwowCloud\Job\Event\QueryExecuted;
use SwowCloud\Job\Logger\LoggerFactory;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class DbQueryExecutedSubscriber implements EventSubscriberInterface
{
    public function __construct(ContainerInterface $container)
    {
        $this->logger = $container->get(LoggerFactory::class)->get('sql', 'sql');
    }

    #[ArrayShape([QueryExecuted::QUERY_EXECUTED => 'string'])]
    public static function getSubscribedEvents(): array
    {
        return [
            QueryExecuted::QUERY_EXECUTED => 'onLogQueryExecuted',
        ];
    }

    public function onLogQueryExecuted(QueryExecuted $event): void
    {
        $sql = $event->sql;
        if (!Arr::isAssoc($event->bindings)) {
            foreach ($event->bindings as $key => $value) {
                $sql = Str::replaceFirst('?', "'{$value}'", $sql);
            }
        } else {
            foreach ($event->bindings as $key => $value) {
                if (str_contains($key, ':')) {
                    $sql = Str::replaceFirst($key, "'{$value}'", $sql);
                }
            }
        }

        $this->logger->info(sprintf('[%s] %s', $event->time, $sql));
    }
}
