<?php
/**
 * This file is part of Serendipity Job
 * @license  https://github.com/serendipity-swow/serendipity-job/blob/main/LICENSE
 */

declare(strict_types=1);

namespace Serendipity\Job\Kernel\Xhprof;

use Serendipity\Job\Db\Command;
use Serendipity\Job\Db\DB;
use Serendipity\Job\Kernel\Http\Request;
use Swow\Http\Server\Connection;

class Xhprof
{
    public static function startPoint(): void
    {
        if (!extension_loaded('tideways_xhprof')) {
            return;
        }
        /* @noinspection PhpUndefinedConstantInspection */
        /* @noinspection PhpUndefinedFunctionInspection */
        tideways_xhprof_enable(TIDEWAYS_XHPROF_FLAGS_MEMORY | TIDEWAYS_XHPROF_FLAGS_MEMORY_MU | TIDEWAYS_XHPROF_FLAGS_MEMORY_PMU | TIDEWAYS_XHPROF_FLAGS_CPU);
    }

    public static function endPoint(Connection $connection, Request $request, bool $insert = true): void
    {
        if (!extension_loaded('tideways_xhprof')) {
            return;
        }
        /** @noinspection PhpUndefinedFunctionInspection */
        $profile = tideways_xhprof_disable();
        $requestTimeFloat = explode(' ', microtime());
        $requestTsMicro = ['sec' => $requestTimeFloat[1], 'usec' => $requestTimeFloat[0] * 1000000];
        $meta = [
            'url' => $request->getUriAsString(),
            'server_name' => env('SERVER_NAME'),
            'get' => json_encode($request->getQueryParams(), JSON_THROW_ON_ERROR),
            'server' => json_encode($_SERVER ?? [], JSON_THROW_ON_ERROR),
            'type' => $request->getMethod(),
            'ip' => $connection->getPeerAddress(),
            'request_time' => $requestTsMicro['sec'],
            'request_time_micro' => $requestTsMicro['usec'],
            'profile' => json_encode(['profile' => $profile], JSON_THROW_ON_ERROR),
            'mu' => $profile['main()']['mu'],
            'pmu' => $profile['main()']['pmu'],
            'ct' => $profile['main()']['ct'],
            'cpu' => $profile['main()']['cpu'],
            'wt' => $profile['main()']['wt'],
        ];
        if ($insert) {
            self::insertXhprofProfile($meta);
        }
    }

    protected static function insertXhprofProfile(array $data): void
    {
        /**
         * @var Command $command
         */
        $command = make(Command::class);
        $command->insert('job_monitor', $data);
        DB::run(function (\PDO $PDO) use ($command): int {
            $statement = $PDO->prepare($command->getSql());

            $this->bindValues($statement, $command->getParams());

            $statement->execute();

            return (int) $PDO->lastInsertId();
        });
    }
}
