<?php
/**
 * This file is part of Serendipity Job
 * @license  https://github.com/serendipity-swow/serendipity-job/blob/main/LICENSE
 */

declare(strict_types=1);

namespace Serendipity\Job\Kernel\Provider;

interface ProviderContract
{
    public function bootApp();

    public function bootRequest();

    public function shutdown();
}
