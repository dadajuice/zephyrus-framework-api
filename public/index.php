<?php

use Zephyrus\Application\Bootstrap;
use Zephyrus\Network\RequestFactory;

/**
 * All application route calls are redirected here by the .htaccess file to start the Zephyrus router engine.
 */
Bootstrap::initializeRoutableControllers($router);

/**
 * Will attempt to execute the request sent by the client. If the url matches a defined route within the project
 * controllers, it will execute the middleware chain and the route processing. If an error arise (route wasn't found,
 * requested HTTP_ACCEPT is not supported or any other exception that wasn't caught by the application) it can be
 * handled here using a try catch or in the error handling.
 */
$router->run(RequestFactory::read());
