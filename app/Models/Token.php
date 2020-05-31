<?php namespace Models;

use Zephyrus\Application\Configuration;
use Zephyrus\Database\Core\Adapters\SqliteAdapter;
use Zephyrus\Database\Core\Database;
use Zephyrus\Exceptions\DatabaseException;
use Zephyrus\Network\RequestFactory;
use Zephyrus\Security\Cryptography;

class Token
{
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
     * @throws TokenException
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
     * @throws TokenException
     * @return Token
     */
    public static function load(): Token
    {
        self::loadDatabase();
        list($value, $resourceIdentifier) = self::getTokenParts();
        $token = self::findTokenByResourceIdentifier($resourceIdentifier);
        if ($token->value == $value) {
            self::deleteToken($token->resourceIdentifier);
            return $token;
        }
        throw new TokenException(TokenException::ERR_INVALID_VALUE);
    }

    /**
     * Token is created and inserted in database when this method is called
     * because it is considered "ready" only then.
     *
     * @throws TokenException
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
     * @throws TokenException
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
     * @return Token
     * @throws TokenException
     */
    private static function findTokenByResourceIdentifier(string $resourceIdentifier): Token
    {
        try {
            $statement = self::$database->query("SELECT * FROM token WHERE resource_id = ?", [$resourceIdentifier]);
            $row = $statement->next();
        } catch (DatabaseException $exception) {
            throw new TokenException(TokenException::ERR_DATABASE);
        }
        if (!$row) {
            throw new TokenException(TokenException::ERR_RESOURCE_NOT_FOUND);
        }
        $row = (object) $row;
        if ($row->expiration < date(FORMAT_DATE_TIME)) {
            self::deleteToken($resourceIdentifier);
            throw new TokenException(TokenException::ERR_EXPIRED);
        }
        $token = new Token($row->resource_id);
        $token->value = $row->value;
        return $token;
    }

    /**
     * Query that take care to properly remove any saved token associated with
     * the associated resource identifier and insert the token value.
     *
     * @throws TokenException
     */
    private function insertToken()
    {
        self::deleteToken($this->resourceIdentifier);
        try {
            self::$database->query("INSERT INTO token(resource_id, value, expiration) VALUES(?, ?, ?)", [
                $this->resourceIdentifier,
                $this->value,
                $this->getExpiration()
            ]);
        } catch (DatabaseException $exception) {
            throw new TokenException(TokenException::ERR_DATABASE);
        }
    }

    /**
     * Removes the token with the given resource identifier from the database.
     *
     * @param int $resourceIdentifier
     * @throws TokenException
     */
    private static function deleteToken(int $resourceIdentifier)
    {
        try {
            self::$database->query("DELETE FROM token WHERE resource_id = ?", [$resourceIdentifier]);
        } catch (DatabaseException $exception) {
            throw new TokenException(TokenException::ERR_DATABASE);
        }
    }

    /**
     * Since this class is build to work independently of any other data
     * sources, it is using a simple sqlite file to store token data.
     *
     * @throws TokenException
     */
    private static function loadDatabase()
    {
        try {
            if (is_null(self::$database)) {
                $adapter = new SqliteAdapter(['dbms' => 'sqlite', 'database' => ROOT_DIR . '/token.db']);
                self::$database = new Database($adapter);
                self::pragma();
                self::createTable();
            }
        } catch (DatabaseException $exception) {
            throw new TokenException(TokenException::ERR_DATABASE);
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
        $configuredExpirationTime = Configuration::getConfiguration('token', 'expiration');
        $currentTimestamp = time() + $configuredExpirationTime;
        return date(FORMAT_DATE_TIME, $currentTimestamp);
    }

    /**
     * @return array
     * @throws TokenException
     */
    private static function getTokenParts(): array
    {
        $tokenString = self::getTokenString();
        if (is_null($tokenString)) {
            throw new TokenException(TokenException::ERR_NOT_PROVIDED);
        }
        $tokenParts = explode(self::IDENTIFIER_SEPARATOR, $tokenString);
        if (count($tokenParts) != 2) {
            throw new TokenException(TokenException::ERR_INVALID_FORMAT);
        }
        return $tokenParts;
    }

    /**
     * @return null | string
     */
    private static function getTokenString(): ?string
    {
        $config = Configuration::getConfiguration('token');
        $request = RequestFactory::read();
        $tokenString = $request->getParameter($config['parameter_name']);
        if (is_null($tokenString)) {
            $tokenString = $request->getHeader($config['header_name']);
        }
        return $tokenString;
    }
}
