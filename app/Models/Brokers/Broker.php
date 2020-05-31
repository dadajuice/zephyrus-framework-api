<?php namespace Models\Brokers;

use Zephyrus\Database\Core\Database;
use Zephyrus\Database\DatabaseBroker;

/**
 * Zephyrus enforces that the way to communicate with your database should use broker instances. This class acts as a
 * middleware, all the other "brokers" should extends this class and thus, you can add project specific processing to
 * this class that every other brokers shall inherit.
 *
 * Class Broker
 * @package Models\Brokers
 */
abstract class Broker extends DatabaseBroker
{
    public function __construct(?Database $database = null)
    {
        parent::__construct($database);
        /*$this->applyConnectionVariables();*/
    }

    /**
     * Sample code if you want to automatically send php data to the database environment which could be used inside a
     * trigger for example. If this has no purpose in your project, you should remove this method.
     */
    /*private function applyConnectionVariables()
    {
        $userId = Session::getInstance()->read('id');
        parent::addSessionVariable('user_id', $user_id);
    }*/
}
