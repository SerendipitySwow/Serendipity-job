<?php
/**
 * This file is part of Swow-Cloud/Job
 * @license  https://github.com/serendipity-swow/serendipity-job/blob/master/LICENSE
 */

declare(strict_types=1);

namespace SwowCloud\Job\Kernel;

/**
 * Make the Matrix
 */
class MatrixService
{
    /**
     * Initialize The Matrix
     */
    public function initMatrix(int $width): array
    {
        //new array
        $line = [];
        $a = 0;

        while ($a < $width) {
            $line[] = [
                'status' => 0,
                'string' => ' ',
                'iteration' => 0,
                'col' => $a,
                'row' => 0,
            ];

            $a++;
        }

        return $line;
    }

    /**
     * Make line of the Matrix (in array)
     */
    public function makeArrayLine(int $width, int $height, array $precedentLine): array
    {
        $line = $precedentLine;
        $a = 0;

        while ($a < $width) {
            if ($precedentLine[$a]['status'] === 1) {
                $line[$a]['string'] = $this->generateRandomCharacater();

                if ($precedentLine[$a]['row'] >= $height - 1) {
                    $line[$a]['row'] = 0;
                } else {
                    $line[$a]['row'] = $precedentLine[$a]['row'] + 1;
                }
            } else {
                $line[$a]['string'] = ' ';
                $line[$a]['iteration'] += 1;

                if ($line[$a]['iteration'] === random_int($line[$a]['iteration'], $line[$a]['iteration'] + 100)) {
                    $line[$a]['status'] = 1;
                    $line[$a]['string'] = $this->generateRandomCharacater();
                }
            }
            $a++;
        }

        return $line;
    }

    /**
     * Generate a random character
     *
     * @throws \Exception
     */
    private function generateRandomCharacater(): string
    {
        $letters = 'abcdefghijklmnopqrstuvwxyz';
        $number = '0123456789';
        $special = '!@#$%^&*()_+-=[]{}|;\':",./<>?';
        $choice = $letters . $special . $number;

        return $choice[random_int(0, strlen($choice) - 1)];
    }
}
