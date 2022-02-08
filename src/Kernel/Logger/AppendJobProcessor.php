<?php
/**
 * This file is part of Swow-Cloud/Job
 * @license  https://github.com/serendipity-swow/serendipity-job/blob/master/LICENSE
 */

declare(strict_types=1);

namespace SwowCloud\Job\Kernel\Logger;

class AppendJobProcessor extends AppendRequestIdProcessor
{
    public const TAG_ID = 'Job';

    public function __invoke(array $record): array
    {
        $record['extra']['tag'] = self::TAG_ID;

        return parent::__invoke($record);
    }
}
