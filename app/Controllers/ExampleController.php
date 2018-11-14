<?php namespace Controllers;

class ExampleController extends ApiController
{
    public function initializeRoutes()
    {
        $this->get("/", "index");
        $this->post("/login", "createToken");
    }

    public function index()
    {
        return $this->json(['userId' => $this->resourceIdentifier]);
    }

    public function createToken()
    {
        $userId = $this->login();
        if ($userId < 1) {
            return $this->error(["Login failed!"]);
        }
        $this->resourceIdentifier = $userId;
        return $this->success();
    }

    private function login(): int
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
