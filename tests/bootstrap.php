<?php
/**
 * This file is part of Swow-Cloud/Job
 * @license  https://github.com/serendipity-swow/serendipity-job/blob/main/LICENSE
 */

declare(strict_types=1);

use Psr\Container\ContainerInterface;
use SwowCloud\Job\Application;

if (!extension_loaded('swow')) {
    exit('Swow extension is required');
}
define('BASE_PATH', dirname(__DIR__));
ini_set('display_errors', 'on');
ini_set('display_startup_errors', 'on');
ini_set('memory_limit', '1G');
error_reporting(E_ALL);
date_default_timezone_set('Asia/Shanghai');

require_once BASE_PATH . '/vendor/autoload.php';

Hyperf\Di\ClassLoader::init();
/** @var ContainerInterface $container */
$container = require BASE_PATH . '/config/container.php';
$application = $container->get(Application::class);
