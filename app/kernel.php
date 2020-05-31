<?php

// region Composer autoloading and Zephyrus instance
// This part is essential for the correct inclusion of the Framework on part with Composer dependency manager. Do not
// modify!
use Zephyrus\Application\Bootstrap;
use Zephyrus\Application\Configuration;
use Zephyrus\Application\ErrorHandler;
use Zephyrus\Application\Localization;
use Zephyrus\Exceptions\LocalizationException;
use Zephyrus\Exceptions\RouteNotFoundException;
use Zephyrus\Network\ResponseFactory;
use Zephyrus\Network\Router;

define('ROOT_DIR', __DIR__ . '/..');
require ROOT_DIR . '/vendor/autoload.php';
$router = new Router();
include(Bootstrap::getHelperFunctionsPath());
Bootstrap::start();
// endregion

// region Session startup
// Optional if your project does not require a session. E.g. an API.
// Session::getInstance()->start();
// endregion

// region Localisation engine
// Optional if you dont want to use the /locale feature. This features enables the use of json files to properly
// organize project messages whether or not you have multiple languages. It is thus highly recommended for a more clean
// and maintainable codebase.
try {

    // The <locale> argument is optional, if none is given the configured locale in config.ini will be used.
    Localization::getInstance()->start('fr_CA');
} catch (LocalizationException $e) {

    // If engine cannot properly start an exception will be thrown and must be corrected to use this feature. Common
    // errors are syntax error in json files. The exception messages should be explicit enough.
    die($e->getMessage());
}
// endregion

// region Custom error handling
setupErrorHandling();

/**
 * Defines how to handle errors and exceptions which reached the main thread (that nobody trapped). These are usage
 * example and should be altered to reflect serious application usage. The ErrorHandler class allows to handle any
 * specific exception as you see fit.
 *
 * Note that using the ErrorHandler changes the way PHP will handle errors at its core if you use notice(), warning()
 * or error().
 */
function setupErrorHandling()
{
    $errorHandler = ErrorHandler::getInstance();

    /**
     * Handles basically every exceptions that were not caught. If the application is configured in "dev", it will
     * automatically send the error as a normal API error using a JSON object containing the error. Useful for any
     * easy handling. If in production, should only return a 500 HTTP Code. Adapt according to your needs.
     */
    $errorHandler->exception(function (Exception $e) {
        $response = ResponseFactory::getInstance()->buildAbortInternalError();
        if (Configuration::getApplicationConfiguration('env') == 'dev') {
            $response = ResponseFactory::getInstance()->buildJson([
                'status' => 'error',
                'errors' => [$e->getMessage()]
            ]);
        }
        $response->send();
    });

    /**
     * Handles when a user tries to access a route that doesn't exists. In this example, it simply returns a 404
     * header. You could implement a custom page to display a significant error, do a flash message and redirect, you
     * could also log the attempt, etc. The exception contains the requested URL and http method.
     */
    $errorHandler->exception(function (RouteNotFoundException $e) {
        ResponseFactory::getInstance()->buildAbortNotFound();
    });

    /**
     * Handles specific case when a database exception occurred. Depends on the need of each application. Some may want
     * to specifically handle this case or catch them in the global Exception. In fact, it is possible to handle every
     * exception specifically if needed.
     */
    //$errorHandler->exception(function (DatabaseException $e) {
    //});
}
// endregion
