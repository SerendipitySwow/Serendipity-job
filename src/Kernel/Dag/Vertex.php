<?php
/**
 * This file is part of Swow-Cloud/Job
 * @license  https://github.com/serendipity-swow/serendipity-job/blob/master/LICENSE
 */

declare(strict_types=1);

namespace SwowCloud\Job\Kernel\Dag;

use Closure;
use JetBrains\PhpStorm\Pure;

class Vertex
{
    public ?string $key = '';

    public ?int $timeout = 0;

    /**
     * @var callable
     */
    public $value;

    /**
     * @var array<Vertex>
     */
    public array $parents = [];

    /**
     * @var array<Vertex>
     */
    public array $children = [];

    public static function make(callable $job, int $timeout = 5 * 1000, string $key = null): self
    {
        $closure = Closure::fromCallable($job);
        if ($key === null) {
            $key = spl_object_hash($closure);
        }

        $v = new self();
        $v->key = $key;
        $v->timeout = $timeout;
        $v->value = $closure;

        return $v;
    }

    #[Pure]
    public static function of(
        Runner $job,
        int $timeout = 5 * 1000,
        string $key = null,
    ): self {
        if ($key === null) {
            $key = spl_object_hash($job);
        }

        $v = new self();
        $v->key = $key;
        $v->value = [$job, 'run'];
        $v->timeout = $timeout;

        return $v;
    }
}
