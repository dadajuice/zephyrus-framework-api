<?php

/**
 * !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
 * !!!!! BOOTSTRAP FILE AUTOMATICALLY LOADED                              !!!!!
 * !!!!! Make sure to properly link the framework.                        !!!!!
 * !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
 */
define('ROOT_DIR', __DIR__ . '/..');
require ROOT_DIR . '/vendor/autoload.php';

use Zephyrus\Application\Configuration;
use Zephyrus\Application\Bootstrap;
use Zephyrus\Application\Session;
use Zephyrus\Network\Router;

$router = new Router();

include(Bootstrap::getHelperFunctionsPath());
if (Configuration::getApplicationConfiguration('env') == 'prod') {
    include('handlers.php');
}
Bootstrap::start();
Session::getInstance()->start();
