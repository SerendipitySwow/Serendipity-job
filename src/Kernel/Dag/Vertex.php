<?php

declare( strict_types = 1 );

namespace Serendipity\Job\Kernel\Dag;

use JetBrains\PhpStorm\Pure;

class Vertex
{
    /**
     * @var null|string
     */
    public ?string $key = '';

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

    public static function make (callable $job, string $key = null): self
    {
        $closure = \Closure::fromCallable($job);
        if ($key === null) {
            $key = spl_object_hash($closure);
        }

        $v = new self();
        $v->key = $key;
        $v->value = $closure;
        return $v;
    }

    #[Pure]
    public static function of (
        Runner $job,
        string $key = null
    ): self {
        if ($key === null) {
            $key = spl_object_hash($job);
        }

        $v = new self();
        $v->key = $key;
        $v->value = [ $job, 'run' ];
        return $v;
    }
}
