<?php

/**
 * This file is included by kernel.php and defines how to handle errors and
 * exceptions which reached the main thread (that nobody trapped). This file
 * should be loaded in production because it is not outputting any information
 * about the errors. Details are logged in errors.log file. You can modify each
 * error handling as you see fit (redirection, disconnection, messages, ...).
 */

use Zephyrus\Application\Configuration;
use Zephyrus\Application\ErrorHandler;
use Zephyrus\Exceptions\RouteNotFoundException;
use Zephyrus\Exceptions\DatabaseException;
use Zephyrus\Network\ContentType;
use Zephyrus\Network\Response;

$errorHandler = new ErrorHandler();

$errorHandler->exception(function (Error $e) {
    sendExceptionError($e);
});

$errorHandler->exception(function (Exception $e) {
    sendExceptionError($e);
});

$errorHandler->exception(function (DatabaseException $e) {
    sendExceptionError($e);
});

$errorHandler->exception(function (RouteNotFoundException $e) {
    sendExceptionError($e);
});

function sendExceptionError(Throwable $exception)
{
    $response = new Response(ContentType::PLAIN, 500);
    if (Configuration::getApplicationConfiguration('env') == 'dev') {
        $response = \Zephyrus\Network\ResponseFactory::getInstance()->buildJson([
            'result' => 'error', 'errors' => [$exception->getMessage()]
        ]);
    }
    $response->send();
}