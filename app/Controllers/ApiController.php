<?php namespace Controllers;

use Models\Token;
use Zephyrus\Application\Configuration;
use Zephyrus\Exceptions\IntrusionDetectionException;
use Zephyrus\Network\Response;

abstract class ApiController extends \Zephyrus\Security\Controller
{
    /**
     * @var string
     */
    protected $resourceIdentifier = "";

    /**
     * Basic sample API authentication with a simple API KEY that the
     * connecting client must provide. Useful for being really easy to
     * implement and is a valid solution for mobile devices.
     *
     * SECURITY WARNING (1) : Knowing that the key needs to be
     * transmitted to the server :
     *
     * - ALWAYS communicate with your API over HTTPS.
     *
     * SECURITY WARNING (2) : Knowing that the key needs to be known to
     * the client :
     *
     * - DO NOT use this method for communication from client side
     *   JavaScript application since it can easily be fetched. In that
     *   case, its more secure to make your client side JS script to
     *   communicate with a server and make this server do the API calls
     *   since the server can more securely "hide" the API KEY.
     *
     * - CONSIDER that if you use this method for mobile device
     *   applications, the API KEY will be compiled with your application
     *   code. There are ways to decompile applications and extract such
     *   constants, so be aware of this particular use case and evaluate
     *   the probability and impact of such attack.
     *
     * - CONSIDER that the following code is a mere example using only one
     *   static API KEY.
     *
     * @return Response | bool
     * @throws \Zephyrus\Exceptions\InvalidCsrfException
     * @throws \Zephyrus\Exceptions\UnauthorizedAccessException
     */
    public function before()
    {
        $keyConfig = Configuration::getConfiguration('key');
        $tokenConfig = Configuration::getConfiguration('token');
        if ($keyConfig['enable']) {
            $apiKey = $this->request->getHeader($keyConfig['header_name']);
            if (is_null($apiKey)) {
                $apiKey = $this->request->getParameter($keyConfig['parameter_name']);
            }
            if ($apiKey != $keyConfig['key']) {
                return $this->abortForbidden();
            }
        }
        if ($tokenConfig['enable']) {
            if ($this->request->getUri()->getPath() != $tokenConfig['login_route']) {
                try {
                    $token = Token::load();
                } catch (\Exception $exception) {
                    return ($tokenConfig['forbidden_on_error'])
                        ? $this->abortForbidden()
                        : $this->error([$exception->getMessage()]);
                }
                $this->resourceIdentifier = $token->getResourceIdentifier();
            }
        }

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
                // return $this->abortForbidden();
            }
        }
        return true;
    }

    protected function json($data): Response
    {
        $tokenConfig = Configuration::getConfiguration('token');
        if ($tokenConfig['enable'] && !empty($this->resourceIdentifier)) {
            try {
                $token = new Token($this->resourceIdentifier);
                $data[$tokenConfig['parameter_name']] = $token->__toString();
            } catch (\Exception $exception) {
                return ($tokenConfig['forbidden_on_error'])
                    ? $this->abortForbidden()
                    : $this->error([$exception->getMessage()]);
            }
        }
        return parent::json($data);
    }

    protected function success(array $data = []): Response
    {
        return $this->json(array_merge(['result' => 'success'], $data));
    }

    protected function error(array $errorMessages = [], array $data = []): Response
    {
        return $this->json(array_merge(['result' => 'error', 'errors' => $errorMessages], $data));
    }
}
