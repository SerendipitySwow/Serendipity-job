<?php
declare(strict_types = 1);
namespace Serendipity\Job\Config;

use Psr\Container\ContainerInterface;
use Serendipity\Job\Contract\ConfigInterface;
use Serendipity\Job\Kernel\Provider\AbstractProvider;
use Serendipity\Job\Util\ApplicationContext;
use function Serendipity\Job\Kernel\make;

class  ConfigProvider extends AbstractProvider
{
    protected static string $interface = ConfigInterface::class;

    public function bootApp() : void
    {
        $container = ApplicationContext::getContainer();
        //TODO 待解决循环依赖的问题
        $factory   = new ConfigFactory($container);
        $container->set(ConfigInterface::class, $factory);
    }

    public function bootRequest() : void
    {

    }
}
