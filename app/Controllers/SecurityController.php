<?php namespace Controllers;

use Zephyrus\Exceptions\IntrusionDetectionException;
use Zephyrus\Exceptions\InvalidCsrfException;
use Zephyrus\Exceptions\UnauthorizedAccessException;
use Zephyrus\Network\Response;
use Zephyrus\Security\Authorization;
use Zephyrus\Security\ContentSecurityPolicy;
use Zephyrus\Security\Controller as ZephyrusBaseController;

/**
 * This controller class acts as a security middleware for the application. All controllers should inherit this
 * middleware to ensure proper security and maintainability. This class should be used to specify authorizations, CSP
 * headers, intrusion detection behaviors, and any another security specific settings for your application. The Zephyrus
 * base security controller which this class extends from contains basic security behaviors that all applications should
 * have (CSRF, security headers and authorization engine).
 *
 * Class SecurityController
 * @package Controllers
 */
abstract class SecurityController extends ZephyrusBaseController
{
    /**
     * This method is called before every route call from inherited controllers and makes sure to check if there is an
     * intrusion detection, or an authorization problem and sets the Content Security Policy headers. Any other security
     * considerations that should be checked BEFORE processing any route should be done here.
     *
     * Parent call should ensure check for basic security measures in any application such as CSRF validation, intrusion
     * detection and authorization access.
     *
     * @return Response|null
     */
    public function before(): ?Response
    {
        $this->applyContentSecurityPolicies();
        $this->setupAuthorizations();

        /**
         * May throw an UnauthorizedAccessException, InvalidCsrfException or IntrusionDetectionException. Exception can
         * be thrown to be caught by children controllers if needed or the error handler. It is recommended to catch
         * them here to ensure a proper uniform handling of the basic security controls.
         */
        try {
            parent::before();
        } catch (IntrusionDetectionException $exception) {
            /**
             * Defines what to do when an attack attempt (mainly XSS and SQL injection) is detected in the application.
             * The impact value represents the severity of the attempt. IntrusionDetection class is a wrapper of the
             * expose library. Be careful about the action chosen to handle such case as there may have false positive
             * for legitimate clients. That is why there are no default action.
             *
             * @see https://github.com/enygma/expose
             */
            $data = $exception->getIntrusionData();
            if ($data['impact'] >= 10) {
                // Do something (logs, database report, redirect, ...)
                // return $this->abortForbidden();
            }
        } catch (InvalidCsrfException $exception) {
            /**
             * Defines what to do when the CSRF token mismatch. Meaning there's an attempt to access a route with no
             * token or with an expired already use token. By default, treat this as a forbidden access to the route.
             * This will break the middleware chain and immediately return the 403 HTTP code and thus ensure protection
             * of the route processing.
             */
            // Do something (logs, database report, redirect, ...)
            return $this->abortForbidden();
        } catch (UnauthorizedAccessException $exception) {
            /**
             * Defines what to do when the route doesn't meet the authorization requirements. By default, treat this as
             * a forbidden access to the route. This will break the middleware chain and immediately return the 403 HTTP
             * code and thus ensure protection of the route processing.
             */
            // Do something (logs, database report, redirect, ...)
            return $this->abortForbidden();
        }

        // No security issue found, continue processing of middleware chain or
        // route processing.
        return null;
    }

    /**
     * Defines the application authorizations for all inherited controllers. For a cleaner and maintainable codebase for
     * large projects with a huge quantities or routes with more or less complex authorization, it should be split
     * across multiple middlewares (overriding the before() method) for specific controller to set the related
     * authorizations.
     */
    private function setupAuthorizations()
    {
        /**
         * The mode specifies the default behavior when no rule has been defined for a route.
         *
         * Blacklist: if a route has no rule, its automatically granted [default]
         * Whitelist: if a route has no rule, its automatically denied.
         *
         * Blacklist should be consider for a little application where there's only an administrative zone that should
         * be protected. Whitelist should be consider for an application that requires a login and that roles change how
         * the application is accessed.
         */
        parent::getAuthorization()->setMode(Authorization::MODE_BLACKLIST);

        /**
         * Rules definition for the authorization system. You must create your own rules based on the needs of your
         * application. You can easily set rules based on a session data or ip address using, respectively, the methods:
         * addSessionRule() and addIpAddressRule(). For any other needs (custom verifying, database calls, ...), you can
         * use the method addRule() which needs a callback.
         *
         * Example below can be read as : create a rule named "admin" (which can later be referenced) that needs the
         * $_SESSION key <AUTH_LEVEL> with the value <admin>.
         */
        parent::getAuthorization()->addSessionRule('admin', 'AUTH_LEVEL', 'admin');

        /**
         * When in whitelist mode (every route that doesn't have a rule is automatically denied), it may be useful to
         * specify a "public" rule for the login screen in example. For a more in depth processing, the callback can
         * receive the url as argument.
         */
        parent::getAuthorization()->addRule('public', function () {
            return true;
        });

        /**
         * Once the rules are defined, you can start to protect your desired routes. First argument of the protect
         * method is the path (written as a controller route) you wish to add authorization requirements. Second
         * argument is the HTTP method to validate (can be combined using the binary OR operator like GET | POST). The
         * ALL constant refers to GET | POST | PUT | PATCH | DELETE. The last argument is the rule's name to fulfil to
         * grant access.
         *
         * Example below can be read as : route "/users", for all HTTP methods, needs the <admin> rule to be fulfilled
         * for the route to be accessible.
         */
        parent::getAuthorization()->protect('/users', Authorization::ALL, 'admin');

        /**
         * Example below can be read as : route "/login", for all HTTP methods, can be accessed by anyone.
         */
        parent::getAuthorization()->protect('/login', Authorization::ALL, 'public');
    }

    /**
     * Defines the Content Security Policies (CSP) to use for all inherited controllers. The ContentSecurityPolicy class
     * helps to craft and maintain the CSP headers easily. These headers should be seriously crafted since they greatly
     * help to prevent cross-site scripting attacks.
     *
     * @see https://content-security-policy.com/
     */
    private function applyContentSecurityPolicies()
    {
        $csp = new ContentSecurityPolicy();
        $csp->setDefaultSources(["'self'"]);
        $csp->setFontSources(["'self'", 'https://fonts.googleapis.com', 'https://fonts.gstatic.com']);
        $csp->setStyleSources(["'self'", 'https://fonts.googleapis.com']);
        $csp->setScriptSources(["'self'", 'https://ajax.googleapis.com', 'https://maps.googleapis.com',
            'https://www.google-analytics.com', 'http://connect.facebook.net']);
        $csp->setChildSources(["'self'", 'http://staticxx.facebook.com']);
        $csp->setImageSources(["'self'", 'data:']);
        $csp->setBaseUri([$this->request->getBaseUrl()]);

        /**
         * The SecureHeader class is the instance that will actually sent all the headers concerning security including
         * the CSP. Other headers includes policy concerning iframe integration, strict transport security and xss
         * protection. These headers are sent automatically from the Zephyrus security controller this class inherits
         * from.
         */
        parent::getSecureHeader()->setContentSecurityPolicy($csp);
    }
}
