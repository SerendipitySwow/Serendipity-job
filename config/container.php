<?php
/**
 * This file is part of Serendipity Job
 * @license  https://github.com/Hyperf-Glory/Serendipity-job/blob/main/LICENSE
 */

declare(strict_types=1);

use Hyperf\Di\Container;
use Hyperf\Di\Definition\DefinitionSourceFactory;
use Psr\Container\ContainerInterface;
use Serendipity\Job\Util\ApplicationContext;

$container = new Container(( new DefinitionSourceFactory(true) )());
if (!$container instanceof ContainerInterface) {
    throw new RuntimeException('The dependency injection container is invalid.');
}

return ApplicationContext::setContainer($container);
