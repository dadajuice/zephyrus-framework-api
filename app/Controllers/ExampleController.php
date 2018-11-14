<?php namespace Controllers;

class ExampleController extends SecurityController
{
    public function initializeRoutes()
    {
        $this->get("/", "index");
    }

    /**
     * Example route which renders a simple page of items.
     */
    public function index()
    {
        return $this->json(['result' => 'success']);
    }
}
