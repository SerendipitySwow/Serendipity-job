<?php
/**
 * This file is part of Swow-Cloud/Job
 * @license  https://github.com/serendipity-swow/serendipity-job/blob/master/LICENSE
 */

declare(strict_types=1);

namespace SwowCloud\Job\Kernel\Dag;

use Hyperf\Engine\Channel;
use Hyperf\Engine\Coroutine;
use SwowCloud\Job\Kernel\Dag\Exception\InvalidArgumentException;
use SwowCloud\Job\Util\Waiter;
use Throwable;

class Dag implements Runner
{
    /**
     * @var array<string,Vertex>
     */
    protected array $vertexes = [];

    protected ?Waiter $waiter = null;

    /**
     * Add a vertex to the dag.
     * It doesn't make sense to add a vertex with the same key more than once.
     * If so they are simply ignored.
     */
    public function addVertex(Vertex $vertex): self
    {
        $this->vertexes[$vertex->key] = $vertex;

        return $this;
    }

    /**
     * Add an edge to the DAG.
     */
    public function addEdge(Vertex $from, Vertex $to): self
    {
        $from->children[] = $to;
        $to->parents[] = $from;

        return $this;
    }

    /**
     * Run the DAG.
     *
     * @param array $args while using the nested dag, $args contains results from the parent dag.
     *                    in other cases, args can be used to modify dag behavior at run time.
     *
     * @throws Throwable
     */
    public function run(array $args = []): array
    {
        $this->waiter = make(Waiter::class);
        $queue = new Channel(1);
        Coroutine::create(function () use ($queue) {
            $this->buildInitialQueue($queue);
        });

        $total = count($this->vertexes);
        $visited = [];
        $results = $args;

        while (count($visited) < $total) {
            $element = $queue->pop();
            if ($element instanceof Throwable) {
                throw $element;
            }
            if (isset($visited[$element->key])) {
                continue;
            }
            // this channel will be closed after the completion of the corresponding task.
            $visited[$element->key] = new Channel();
            $this->waiter->wait(function () use ($queue, $visited, $element, &$results) {
                try {
                    $results[$element->key] = call($element->value, [$results]);
                } catch (Throwable $e) {
                    $queue->push($e);
                    throw $e;
                }
                $visited[$element->key]->close();
                if (empty($element->children)) {
                    return;
                }
                Coroutine::create(function () use ($element, $queue, $visited) {
                    $this->scheduleChildren($element, $queue, $visited);
                });
            }, (int) ($element->timeout / 1000));
        }

        // wait for all pending tasks to resolve
        foreach ($visited as $element) {
            $element->pop();
        }

        return $results;
    }

    private function scheduleChildren(Vertex $element, Channel $queue, array $visited): void
    {
        foreach ($element->children as $child) {
            // Only schedule child if all parents but this one is complete
            foreach ($child->parents as $parent) {
                if ($parent->key === $element->key) {
                    continue;
                }
                if (!isset($visited[$parent->key])) {
                    continue 2;
                }
                // Parent might be running. Wait until completion.
                $visited[$parent->key]->pop();
            }
            $queue->push($child);
        }
    }

    private function buildInitialQueue(Channel $queue): void
    {
        $roots = [];
        foreach ($this->vertexes as $vertex) {
            if (empty($vertex->parents)) {
                $roots[] = $vertex;
            }
        }

        if (empty($roots)) {
            throw new InvalidArgumentException('no roots can be found in dag');
        }

        foreach ($roots as $root) {
            $queue->push($root);
        }
    }
}
