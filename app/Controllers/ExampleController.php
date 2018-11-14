<?php namespace Controllers;

class ExampleController extends ApiController
{
    public function initializeRoutes()
    {
        $this->get("/", "index");
        $this->post("/login", "login");
    }

    public function index()
    {
        // If token is enabled, this route will not render unless the client
        // provides a valid token for the corresponding resource identifier.
        return $this->json(['userId' => $this->resourceIdentifier]);
    }

    public function login()
    {
        $userId = $this->authenticate();
        if ($userId < 1) {
            return $this->error(["Login failed!"]);
        }

        // Only need to apply the generic resource identifier to automatically
        // generate a token.
        $this->resourceIdentifier = $userId;
        return $this->success();
    }

    /**
     * Quick example of authentication, should include database calls for
     * proper user management.
     *
     * @return int
     */
    private function authenticate(): int
    {
        $username = $this->request->getParameter('username');
        $password = $this->request->getParameter('password');
        if ($username == 'bob' && $password == 'Omega123') {
            return 1;
        }
        if ($username == 'lewis' && $password == 'Omega123') {
            return 2;
        }
        return 0;
    }
}
