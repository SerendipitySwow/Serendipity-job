<?php
/**
 * This file is part of Swow-Cloud/Job
 * @license  https://github.com/serendipity-swow/serendipity-job/blob/main/LICENSE
 */

declare(strict_types=1);

/**
 * @internal
 * @coversNothing
 */
class Test
{
    protected $context;

    public function __construct()
    {
        $this->context = new \ArrayObject();
    }

    public function getContext()
    {
        return $this->context;
    }
}
var_dump((new Test())->getContext());
