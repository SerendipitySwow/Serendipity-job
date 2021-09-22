<?php
/**
 * This file is part of Serendipity Job
 * @license  https://github.com/serendipity-swow/serendipity-job/blob/main/LICENSE
 */

declare(strict_types=1);

namespace Serendipity\Job\Redis\Lua;

interface ScriptInterface
{
    public function getScript(): string;

    public function format($data);

    public function eval(array $arguments = [], $sha = true);
}
