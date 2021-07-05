<?php
/**
 * This file is part of Serendipity Job
 * @license  https://github.com/serendipitySwow/Serendipity-job/blob/main/LICENSE
 */

declare(strict_types=1);

use Hyperf\Di\Container;
use Hyperf\Di\Definition\DefinitionSourceFactory;
use Hyperf\Utils\ApplicationContext;
use Psr\Container\ContainerInterface;

$container = new Container(( new DefinitionSourceFactory(true) )());
if (!$container instanceof ContainerInterface) {
    throw new RuntimeException('The dependency injection container is invalid.');
}

return ApplicationContext::setContainer($container);
