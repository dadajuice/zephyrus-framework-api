<?php

use PHPUnit\Framework\TestCase;
use Zephyrus\Database\Database;

abstract class BaseDatabaseTest extends TestCase
{
    /**
     * @var Database
     */
    protected static $db;

    public static function setUpBeforeClass()
    {
        self::$db = Database::buildFromConfiguration();
        /**
         * Using the default database configurations, the database will use SQLite in memory mode. You should create
         * the entire database structure in this class which should be inherited by every database based tests.
         */
        self::$db->query('CREATE TABLE user(user_id INTEGER PRIMARY KEY AUTOINCREMENT, last_name TEXT, first_name TEXT, email TEXT);');
    }
}