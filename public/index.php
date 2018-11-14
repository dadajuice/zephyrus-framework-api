<?php

Zephyrus\Application\Bootstrap::initializeRoutableControllers($router);
$router->run(Zephyrus\Network\RequestFactory::read());