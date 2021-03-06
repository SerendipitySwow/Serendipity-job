<?php
/**
 * This file is part of Swow-Cloud/Job
 * @license  https://github.com/serendipity-swow/serendipity-job/blob/master/LICENSE
 */

declare(strict_types=1);

namespace SwowCloud\Job\Crontab;

use Carbon\Carbon;
use InvalidArgumentException;
use JetBrains\PhpStorm\ArrayShape;

class Parser
{
    /**
     *  解析crontab的定时格式，linux只支持到分钟/，这个类支持到秒.
     *
     * @param string $crontabString :
     *                              0    1    2    3    4    5
     *                              *    *    *    *    *    *
     *                              -    -    -    -    -    -
     *                              |    |    |    |    |    |
     *                              |    |    |    |    |    +----- day of week (0 - 6) (Sunday=0)
     *                              |    |    |    |    +----- month (1 - 12)
     *                              |    |    |    +------- day of month (1 - 31)
     *                              |    |    +--------- hour (0 - 23)
     *                              |    +----------- min (0 - 59)
     *                              +------------- sec (0-59)
     *
     *@return Carbon[]
     * @noinspection TypeUnsafeArraySearchInspection*@throws InvalidArgumentException
     * @noinspection TypeUnsafeArraySearchInspection
     */
    public function parse(string $crontabString, int|Carbon $startTime = null): array
    {
        if (!$this->isValid($crontabString)) {
            throw new InvalidArgumentException('Invalid cron string: ' . $crontabString);
        }
        $startTime = $this->parseStartTime($startTime);
        $date = $this->parseDate($crontabString);
        if (in_array((int) date('i', $startTime), $date['minutes']) &&
            in_array((int) date('G', $startTime), $date['hours']) &&
            in_array((int) date('j', $startTime), $date['day']) &&
            in_array((int) date('w', $startTime), $date['week']) &&
            in_array((int) date('n', $startTime), $date['month'])
        ) {
            $result = [];
            foreach ($date['second'] as $second) {
                $result[] = Carbon::createFromTimestamp($startTime + $second);
            }

            return $result;
        }

        return [];
    }

    /** @noinspection NotOptimalRegularExpressionsInspection
     * @noinspection RegExpRedundantEscape
     */
    public function isValid(string $crontabString): bool
    {
        return !(!preg_match(
            '/^((\*(\/[0-9]+)?)|[0-9\-\,\/]+)\s+((\*(\/[0-9]+)?)|[0-9\-\,\/]+)\s+((\*(\/[0-9]+)?)|[0-9\-\,\/]+)\s+((\*(\/[0-9]+)?)|[0-9\-\,\/]+)\s+((\*(\/[0-9]+)?)|[0-9\-\,\/]+)\s+((\*(\/[0-9]+)?)|[0-9\-\,\/]+)$/i',
            trim($crontabString)
        ) && !preg_match(
            '/^((\*(\/[0-9]+)?)|[0-9\-\,\/]+)\s+((\*(\/[0-9]+)?)|[0-9\-\,\/]+)\s+((\*(\/[0-9]+)?)|[0-9\-\,\/]+)\s+((\*(\/[0-9]+)?)|[0-9\-\,\/]+)\s+((\*(\/[0-9]+)?)|[0-9\-\,\/]+)$/i',
            trim($crontabString)
        ));
    }

    /**
     * Parse each segment of crontab string.
     * @return array<int,int>
     *
     * @noinspection NotOptimalIfConditionsInspection*/
    protected function parseSegment(string $string, int $min, int $max, int $start = null): array
    {
        if ($start === null || $start < $min) {
            $start = $min;
        }
        $result = [];
        if ($string === '*') {
            for ($i = $start; $i <= $max; ++$i) {
                $result[] = $i;
            }
        } elseif (str_contains($string, ',')) {
            $exploded = explode(',', $string);
            foreach ($exploded as $value) {
                if (str_contains($value, '/') || str_contains($string, '-')) {
                    /** @noinspection SlowArrayOperationsInLoopInspection */
                    $result = array_merge($result, $this->parseSegment($value, $min, $max, $start));
                    continue;
                }

                if (!$this->between((int) $value, (max($min, $start)), $max)) {
                    continue;
                }
                $result[] = (int) $value;
            }
        } elseif (str_contains($string, '/')) {
            $exploded = explode('/', $string);
            if (str_contains($exploded[0], '-')) {
                [$nMin, $nMax] = explode('-', $exploded[0]);
                $nMin > $min && $min = (int) $nMin;
                $nMax < $max && $max = (int) $nMax;
            }
            // If the value of start is larger than the value of min, the value of start should equal with the value of min.
            $start < $min && $start = $min;
            for ($i = $start; $i <= $max;) {
                $result[] = $i;
                /* @noinspection PhpWrongStringConcatenationInspection */
                $i += $exploded[1];
            }
        } elseif (str_contains($string, '-')) {
            $result = array_merge($result, $this->parseSegment($string . '/1', $min, $max, $start));
        } elseif ($this->between((int) $string, max($min, $start), $max)) {
            $result[] = (int) $string;
        }

        return $result;
    }

    /**
     * Determine if the $value is between in $min and $max ?
     */
    private function between(int $value, int $min, int $max): bool
    {
        return $value >= $min && $value <= $max;
    }

    private function parseStartTime(int|Carbon|null $startTime): int
    {
        if ($startTime instanceof Carbon) {
            $startTime = $startTime->getTimestamp();
        } elseif ($startTime === null) {
            $startTime = time();
        }
        if (!is_numeric($startTime)) {
            throw new InvalidArgumentException("\$startTime have to be a valid unix timestamp ({$startTime} given)");
        }

        return (int) $startTime;
    }

    /**
     * @return array<string,int[]>
     */
    #[ArrayShape(['second' => 'int[]|mixed', 'minutes' => 'mixed', 'hours' => 'mixed', 'day' => 'mixed', 'month' => 'mixed', 'week' => 'mixed'])]
    private function parseDate(string $crontabString): array
    {
        /** @var array<int,string> $cron */
        $cron = preg_split('/[\\s]+/i', trim($crontabString));
        if (count($cron) === 6) {
            $date = [
                'second' => $this->parseSegment($cron[0], 0, 59),
                'minutes' => $this->parseSegment($cron[1], 0, 59),
                'hours' => $this->parseSegment($cron[2], 0, 23),
                'day' => $this->parseSegment($cron[3], 1, 31),
                'month' => $this->parseSegment($cron[4], 1, 12),
                'week' => $this->parseSegment($cron[5], 0, 6),
            ];
        } else {
            $date = [
                'second' => [1 => 0],
                'minutes' => $this->parseSegment($cron[0], 0, 59),
                'hours' => $this->parseSegment($cron[1], 0, 23),
                'day' => $this->parseSegment($cron[2], 1, 31),
                'month' => $this->parseSegment($cron[3], 1, 12),
                'week' => $this->parseSegment($cron[4], 0, 6),
            ];
        }

        return $date;
    }
}
