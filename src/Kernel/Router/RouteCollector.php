<?php
/**
 * This file is part of Serendipity Job
 * @license  https://github.com/serendipity-swow/serendipity-job/blob/main/LICENSE
 */

declare(strict_types=1);

namespace Serendipity\Job\Kernel\Router;

use FastRoute\RouteCollector as FastRouteCollector;

class RouteCollector extends FastRouteCollector
{
    /** @var array List of middlewares called using the addMiddleware() method. */
    private array $currentMiddlewares = [];

    /**
     * Encapsulate all the routes that are added from $func(Router) with this middleware.
     *
     * If the return value of the middleware is false, throws a RouteMiddlewareFailedException.
     *
     * @param string|string[] $middlewareClass The middleware to use
     */
    public function addMiddleware(array|string $middlewareClass, callable $func): void
    {
        array_push($this->currentMiddlewares, ...(array) $middlewareClass);
        $func($this);
        array_pop($this->currentMiddlewares);
    }

    /**
     * @param string|string[] $httpMethod
     * @param string $route
     * @param mixed $handler
     */
    public function addRoute($httpMethod, $route, $handler): void
    {
        $handler = (array) $handler;
        $handler['middlewares'] = $this->currentMiddlewares;
        parent::addRoute($httpMethod, $route, $handler);
    }
}
