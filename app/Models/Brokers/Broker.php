<?php namespace Models\Brokers;

use Zephyrus\Database\Database;

abstract class Broker extends \Zephyrus\Database\Broker
{
    abstract protected function load(array $row);

    public function __construct(?Database $database = null)
    {
        parent::__construct($database);
        /*$this->applyConnectionVariables();*/
    }

    public function selectInstance($query, $parameters = [], $allowedTags = "")
    {
        $result = parent::selectSingle($query, $parameters, $allowedTags);
        return (!$result) ? null : $this->load($result);
    }

    public function selectInstances($query, $parameters = [], $allowedTags = "")
    {
        $results = [];
        foreach (parent::select($query, $parameters, $allowedTags) as $row) {
            $results[] = $this->load($row);
        }
        return $results;
    }

    /**
     * Sample code if you want to automatically send php data to the database
     * environment which could be used inside a trigger for example. If this
     * has no purpose in your project, you should remove this method.
     */
    /*private function applyConnectionVariables()
    {
        $userId = Session::getInstance()->read('id');
        try {
            parent::query("SET @user_id = ?", [$userId]);
        } catch (\Exception $e) {

        }
    }*/
}
