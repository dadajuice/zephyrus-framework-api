<?php

/**
 * This file is included by kernel.php and defines how to handle errors and
 * exceptions which reached the main thread (that nobody trapped). This file
 * should be loaded in production because it is not outputting any information
 * about the errors. Details are logged in errors.log file. You can modify each
 * error handling as you see fit (redirection, disconnection, messages, ...).
 */
use Zephyrus\Application\ErrorHandler;
use Zephyrus\Exceptions\RouteNotFoundException;
use Zephyrus\Exceptions\DatabaseException;

$errorHandler = new ErrorHandler();

$errorHandler->exception(function (Error $e) {
});

$errorHandler->exception(function (Exception $e) {
});

$errorHandler->exception(function (DatabaseException $e) {
});

$errorHandler->exception(function (RouteNotFoundException $e) {
});

// Its recommended to catch in security middleware
//$errorHandler->exception(function(UnauthorizedAccessException $e) {
//});

// Its recommended to catch in security middleware
//$errorHandler->exception(function (InvalidCsrfException $e) {
//});

// Its recommended to catch in security middleware
//$errorHandler->exception(function (InvalidCsrfException $e) {
//});
