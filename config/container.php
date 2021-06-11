<?php
declare( strict_types = 1 );

use Hyperf\Di\Container;
use Hyperf\Di\Definition\DefinitionSourceFactory;
use Serendipity\Job\Util\ApplicationContext;
use Psr\Container\ContainerInterface;

$container = new Container(( new DefinitionSourceFactory(true) )());
if (!$container instanceof ContainerInterface) {
    throw new RuntimeException('The dependency injection container is invalid.');
}
return ApplicationContext::setContainer($container);
