<?php namespace Controllers;

use Zephyrus\Exceptions\IntrusionDetectionException;

abstract class SecurityController extends \Zephyrus\Security\Controller
{
    public function before()
    {
        // May throw an UnauthorizedAccessException, InvalidCsrfException or
        // IntrusionDetectionException. Its possible to catch the exception directly
        // here or in the error handling file.
        try {
            parent::before();
        } catch (IntrusionDetectionException $exception) {
            /**
             * Defines what to do when an attack attempt (mainly XSS and SQL injection) is
             * detected in the application. The impact value represents the severity of the
             * attempt. The code below only logs the attempt in the security.log when impact
             * is equal or higher than 10. Do nothing more to limit false positive effect on
             * legit users. IntrusionDetection class is a wrapper of the expose library.
             *
             * @see https://github.com/enygma/expose
             */
            $data = $exception->getIntrusionData();
            if ($data['impact'] >= 10) {
                // Do something (logs, ...)
            }
        }
    }
}
