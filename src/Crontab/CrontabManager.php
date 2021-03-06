<?php
/**
 * This file is part of Swow-Cloud/Job
 * @license  https://github.com/serendipity-swow/serendipity-job/blob/master/LICENSE
 */

declare(strict_types=1);

namespace SwowCloud\Job\Crontab;

class CrontabManager
{
    /**
     * @var Crontab[]
     */
    protected array $crontabs = [];

    protected Parser $parser;

    public function __construct(Parser $parser)
    {
        $this->parser = $parser;
    }

    public function register(Crontab $crontab): bool
    {
        if (!$this->isValidCrontab($crontab) || !$crontab->isEnable()) {
            return false;
        }
        $this->crontabs[$crontab->getName()] = $crontab;

        return true;
    }

    /**
     * @return array<int,\SwowCloud\Job\Crontab\Crontab>
     */
    public function parse(): array
    {
        $result = [];
        $crontabs = $this->getCrontabs();
        $last = time();
        foreach ($crontabs as $key => $crontab) {
            if (!$crontab instanceof Crontab) {
                unset($this->crontabs[$key]);
                continue;
            }
            $time = $this->parser->parse($crontab->getRule(), $last);
            if ($time) {
                foreach ($time as $t) {
                    $result[] = clone $crontab->setExecuteTime($t);
                }
            }
        }

        return $result;
    }

    /**
     * @return \SwowCloud\Job\Crontab\Crontab[]
     */
    public function getCrontabs(): array
    {
        return $this->crontabs;
    }

    private function isValidCrontab(Crontab $crontab): bool
    {
        return $crontab->getName() && $crontab->getRule() && $crontab->getCallback() && $this->parser->isValid($crontab->getRule());
    }
}
