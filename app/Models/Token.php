<?php namespace Models;

use PDO;
use Zephyrus\Application\Configuration;
use Zephyrus\Database\Broker;
use Zephyrus\Database\Database;
use Zephyrus\Exceptions\DatabaseException;
use Zephyrus\Network\RequestFactory;
use Zephyrus\Security\Cryptography;

class Token
{
    public const PARAMETER_NAME = "token";
    private const IDENTIFIER_SEPARATOR = "|";

    /**
     * @var Database
     */
    private static $database;

    /**
     * @var string
     */
    private $value;

    /**
     * @var string
     */
    private $resourceIdentifier;

    /**
     * Use the constructor to prepare a new token with the given resource
     * identifier. Will be properly generated only when the toString method
     * is called.
     *
     * @throws DatabaseException
     * @param string $resourceIdentifier
     */
    public function __construct(string $resourceIdentifier)
    {
        $this->resourceIdentifier = $resourceIdentifier;
        self::loadDatabase();
    }

    /**
     * Tries to obtain the token from the request parameters. If nothing
     * matches, null is returned. Otherwise, the token is build from the
     * database data.
     *
     * @throws DatabaseException
     * @return Token | null
     */
    public static function read(): ?Token
    {
        self::loadDatabase();
        $request = RequestFactory::read();
        $tokenString = $request->getParameter(self::PARAMETER_NAME);
        if (is_null($tokenString)) {
            return null; // exception token not provided
        }
        $tokenParts = explode(self::IDENTIFIER_SEPARATOR, $tokenString);
        if (count($tokenParts) != 2) {
            return null; // exception invalid token format
        }
        list($value, $resourceIdentifier) = $tokenParts;

        $token = self::findTokenByResourceIdentifier($resourceIdentifier);
        if (!is_null($token) && $token->value == $value) {
            self::deleteToken($token->resourceIdentifier);
            return $token;
        }
        return null; // exception invalid token
    }

    /**
     * Token is created and inserted in database when this method is called
     * because it is considered "ready" only then.
     *
     * @throws DatabaseException
     * @return string
     */
    public function __toString(): string
    {
        $this->generate();
        return $this->value . self::IDENTIFIER_SEPARATOR . $this->resourceIdentifier;
    }

    /**
     * @return string
     */
    public function getResourceIdentifier(): string
    {
        return $this->resourceIdentifier;
    }

    /**
     * Generates a cryptographic random string of 64 characters and proceed to
     * insert the token with the associated resource identifier to the
     * database.
     *
     * @throws DatabaseException
     */
    private function generate()
    {
        $this->value = Cryptography::randomString(64);
        $this->insertToken();
    }

    /**
     * Tries to fetch the token associated to the given resource identifier. Returns
     * null if none is found, otherwise returns the actual instanced token.
     *
     * @param string $resourceIdentifier
     * @return Token | null
     * @throws DatabaseException
     */
    private static function findTokenByResourceIdentifier(string $resourceIdentifier): ?Token
    {
        $statement = self::$database->query("SELECT * FROM token WHERE resource_id = ?", [$resourceIdentifier]);
        $row = (object) $statement->next(PDO::FETCH_OBJ);
        if (empty($row)) {
            return null; // Exception resource not found
        }
        if ($row->expiration < date(Broker::SQL_FORMAT_DATE_TIME)) {
            self::deleteToken($resourceIdentifier);
            return null; // Exception expiration
        }
        $token = new Token($row->resource_id);
        $token->value = $row->value;
        return $token;
    }

    /**
     * Query that take care to properly remove any saved token associated with
     * the associated resource identifier and insert the token value.
     *
     * @throws DatabaseException
     */
    private function insertToken()
    {
        self::deleteToken($this->resourceIdentifier);
        self::$database->query("INSERT INTO token(resource_id, value, expiration) VALUES(?, ?, ?)", [
            $this->resourceIdentifier,
            $this->value,
            $this->getExpiration()
        ]);
    }

    /**
     * Removes the token with the given resource identifier from the database.
     *
     * @param int $resourceIdentifier
     * @throws DatabaseException
     */
    private static function deleteToken(int $resourceIdentifier)
    {
        self::$database->query("DELETE FROM token WHERE resource_id = ?", [$resourceIdentifier]);
    }

    /**
     * Since this class is build to work independently of any other data
     * sources, it is using a simple sqlite file to store token data.
     *
     * @throws DatabaseException
     */
    private static function loadDatabase()
    {
        if (is_null(self::$database)) {
            self::$database = new Database('sqlite:' . ROOT_DIR . '/token.db');
            self::pragma();
            self::createTable();
        }
    }

    /**
     * Send PRAGMA commands to prevent locking problems over virtual sharing.
     *
     * @throws DatabaseException
     */
    private static function pragma()
    {
        self::$database->query("PRAGMA temp_store=MEMORY");
        self::$database->query("PRAGMA journal_mode=MEMORY");
    }

    /**
     * Creates initial token table if needed.
     *
     * @throws DatabaseException
     */
    private static function createTable()
    {
        self::$database->query("CREATE TABLE IF NOT EXISTS token(
            id INTEGER PRIMARY KEY AUTOINCREMENT, 
            resource_id TEXT, 
            value TEXT, 
            expiration TEXT
        )");
    }

    /**
     * @return string
     */
    private function getExpiration(): string
    {
        $configuredExpirationTime = Configuration::getConfiguration('api', 'expiration');
        $currentTimestamp = time() + $configuredExpirationTime;
        return date(Broker::SQL_FORMAT_DATE_TIME, $currentTimestamp);
    }
}
