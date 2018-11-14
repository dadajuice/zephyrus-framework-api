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
     * @throws \Zephyrus\Exceptions\IntrusionDetectionException
     * @throws \Zephyrus\Exceptions\InvalidCsrfException
     * @throws \Zephyrus\Exceptions\UnauthorizedAccessException
     */
    public function before()
    {
        $apiConfig = Configuration::getConfiguration('api');
        if ($apiConfig['enable_api_key']) {
            $apiKey = $this->request->getHeader('X-API-KEY');
            if (is_null($apiKey)) {
                $apiKey = $this->request->getParameter('apikey');
            }
            if ($apiKey != $apiConfig['api_key']) {
                return $this->abortForbidden();
            }
        }
        if ($apiConfig['enable_token']) {
            if ($this->request->getUri()->getPath() != $apiConfig['login_route']) {
                $token = Token::read();
                if (is_null($token)) {
                    return $this->abortForbidden();
                }
                $this->resourceIdentifier = $token->getResourceIdentifier();
            }
        }
        parent::before();
        return true;
    }

    public function after(?Response $response)
    {
        return parent::after($response);
    }

    /**
     * Basic method to quickly returns a success response to the client in JSON
     * format. Additional data can be sent if needed.
     *
     * Basic Structure is :
     *
     * {
     *     "result": "success"
     * }
     *
     * With the following data (['foo' => 3, 'bar' => 'test'] structure is :
     *
     * {
     *    "result": "success",
     *    "foo": 3,
     *    "bar": "test"
     * }
     *
     * @param array $data
     * @return Response
     */
    protected function success(array $data = []): Response
    {
        return $this->json(array_merge(['result' => 'success'], $data));
    }

    protected function json($data): Response
    {
        if (!empty($this->resourceIdentifier)) {
            $token = new Token($this->resourceIdentifier);
            $data[Token::PARAMETER_NAME] = $token->__toString();
        }
        return parent::json($data);
    }

    /**
     * Basic method to quickly returns an error response to the client in JSON
     * format. Additional data can be sent if needed.
     *
     * Basic Structure is :
     *
     * {
     *     "result": "error"
     *     "errors": [
     *         "Email is invalid",
     *         "Zip code is invalid"
     *     ]
     * }
     *
     * With the following data (['foo' => 3, 'bar' => 'test'] structure is :
     *
     * {
     *     "result": "error"
     *     "errors": [
     *         "Email is invalid",
     *         "Zip code is invalid"
     *     ]
     *    "foo": 3,
     *    "bar": "test"
     * }
     *
     * @param array $data
     * @return Response
     */
    protected function error(array $errorMessages = [], array $data = []): Response
    {
        return $this->json(array_merge(['result' => 'error', 'errors' => $errorMessages], $data));
    }
}
