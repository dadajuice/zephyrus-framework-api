<?php namespace Controllers;

use Models\Token;
use Zephyrus\Application\Configuration;
use Zephyrus\Network\Response;

abstract class ApiController extends SecurityController
{
    /**
     * @var string
     */
    protected $resourceIdentifier = "";

    /**
     * Basic sample API authentication with a simple API KEY that the connecting client must provide. Useful for being
     * really easy to implement and is a valid solution for mobile devices.
     *
     * SECURITY WARNING (1) : Knowing that the key needs to be transmitted to the server :
     *
     * - ALWAYS communicate with your API over HTTPS.
     *
     * SECURITY WARNING (2) : Knowing that the key needs to be known to the client :
     *
     * - DO NOT use this method for communication from client side JavaScript application since it can easily be
     *   fetched. In that case, its more secure to make your client side JS script to communicate with a server and
     *   make this server do the API calls since the server can more securely "hide" the API KEY.
     *
     * - CONSIDER that if you use this method for mobile device applications, the API KEY will be compiled with your
     *   application code. There are ways to decompile applications and extract such constants, so be aware of this
     *   particular use case and evaluate the probability and impact of such attack.
     *
     * - CONSIDER that the following code is a mere example using only one static API KEY.
     *
     * @return Response|null
     */
    public function before(): ?Response
    {
        if (($apiKeyCheckResponse = $this->checkApiKey()) instanceof Response) {
            return $apiKeyCheckResponse;
        }
        if (($tokenCheckResponse = $this->checkToken()) instanceof Response) {
            return $tokenCheckResponse;
        }
        return parent::before();
    }

    /**
     * Returns a successful response to the client. Should be used for every successful response to ensure a proper
     * uniformity. This will always have status JSON property with value "success" which can then be easily verified
     * in client app.
     *
     * @param array $data
     * @return Response
     */
    protected function success(array $data = []): Response
    {
        return $this->json(array_merge(['status' => 'success'], $data));
    }

    /**
     * Returns an error response to the client. Should be used for every error to be sent to ensure a proper
     * uniformity. This will always have status JSON property with value "error" and a property "errors" containing
     * all error messages which can then be easily verified and processed in client app.
     *
     * Depending on the style of API design, you may use the various abort methods to use HTTP code as the only
     * error handling.
     *
     * @param array $errorMessages
     * @param array $data
     * @return Response
     */
    protected function error(array $errorMessages = [], array $data = []): Response
    {
        return $this->json(array_merge(['status' => 'error', 'errors' => $errorMessages], $data));
    }

    /**
     * Overrides the default json method to automatically include the token if its enabled in the config.ini file.
     *
     * @param mixed $data
     * @return Response
     */
    protected function json($data): Response
    {
        $tokenConfig = Configuration::getConfiguration('token');
        if ($tokenConfig['enable'] && !empty($this->resourceIdentifier)) {
            try {
                $token = new Token($this->resourceIdentifier);
                $data[$tokenConfig['parameter_name']] = $token->__toString();
            } catch (\Exception $exception) {
                return ($tokenConfig['force_forbidden'])
                    ? $this->abortForbidden()
                    : $this->error([$exception->getMessage()]);
            }
        }
        return parent::json($data);
    }

    /**
     * Tries to validate a given token if the enable configuration is set to true in the config.ini file. Will return
     * either an uniform error handling using the error method or a FORBIDDEN http response depending on the
     * force_forbidden config parameter.
     *
     * @return Response|null
     */
    private function checkToken(): ?Response
    {
        $tokenConfig = Configuration::getConfiguration('token');
        if ($tokenConfig['enable'] && $this->request->getUri()->getPath() != $tokenConfig['login_route']) {
            try {
                $token = Token::load();
            } catch (\Exception $exception) {
                return ($tokenConfig['force_forbidden'])
                    ? $this->abortForbidden()
                    : $this->error([$exception->getMessage()]);
            }
            $this->resourceIdentifier = $token->getResourceIdentifier();
        }
        return null;
    }

    /**
     * Tries to validate the given api key by matching with the config.ini configurations.
     *
     * @return Response|null
     */
    private function checkApiKey(): ?Response
    {
        $keyConfig = Configuration::getConfiguration('key');
        if ($keyConfig['enable']) {
            $apiKey = $this->request->getHeader($keyConfig['header_name']);
            if (is_null($apiKey)) {
                $apiKey = $this->request->getParameter($keyConfig['parameter_name']);
            }
            if ($apiKey != $keyConfig['key']) {
                return $this->abortForbidden();
            }
        }
        return null;
    }
}
