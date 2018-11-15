<?php

define('ROOT_DIR', __DIR__ . '/..');
require ROOT_DIR . '/vendor/autoload.php';

use Zephyrus\Application\Bootstrap;
use Zephyrus\Network\Router;

$router = new Router();

include(Bootstrap::getHelperFunctionsPath());
include('handlers.php');
Bootstrap::start();
