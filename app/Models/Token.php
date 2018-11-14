<?php namespace Models;

use PDO;
use Zephyrus\Database\Database;
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

    public static function read(): ?Token
    {
        self::loadDatabase();
        $request = RequestFactory::read();
        $tokenString = $request->getParameter(self::PARAMETER_NAME);
        if (is_null($tokenString)) {
            return null;
        }
        $tokenParts = explode(self::IDENTIFIER_SEPARATOR, $tokenString);
        if (count($tokenParts) != 2) {
            return null;
        }
        list($value, $resourceIdentifier) = $tokenParts;

        $token = self::findTokenByResourceIdentifier($resourceIdentifier);
        if (!is_null($token) && $token->value == $value) {
            self::deleteToken($token->resourceIdentifier);
            return $token;
        }
        return null;
    }

    public function __construct(string $resourceIdentifier)
    {
        $this->resourceIdentifier = $resourceIdentifier;
        self::loadDatabase();
    }

    public function __toString(): string
    {
        $this->generate();
        return $this->value . self::IDENTIFIER_SEPARATOR . $this->resourceIdentifier;
    }

    public function getResourceIdentifier()
    {
        return $this->resourceIdentifier;
    }

    /**
     * Generates a cryptographic random string of 64 characters, consider
     * updating this method for a different mechanism.
     */
    private function generate()
    {
        $this->value = Cryptography::randomString(64);
        $this->insertToken();
    }

    private static function loadDatabase()
    {
        if (is_null(self::$database)) {
            self::$database = new Database('sqlite:' . ROOT_DIR . '/token.db');
            self::$database->query("PRAGMA temp_store=MEMORY");
            self::$database->query("PRAGMA journal_mode=MEMORY");
            self::$database->query("CREATE TABLE IF NOT EXISTS token(id INTEGER PRIMARY KEY AUTOINCREMENT, resource_id TEXT, value TEXT)");
        }
    }

    private static function findTokenByResourceIdentifier(string $resourceIdentifier): ?Token
    {
        $statement = self::$database->query("SELECT * FROM token WHERE resource_id = ?", [$resourceIdentifier]);
        $row = $statement->next(PDO::FETCH_OBJ);
        if (empty($row)) {
            return null;
        }
        $token = new Token($row->resource_id);
        $token->value = $row->value;
        return $token;
    }

    private static function deleteToken(int $resourceIdentifier)
    {
        self::$database->query("DELETE FROM token WHERE resource_id = ?", [$resourceIdentifier]);
    }

    private function insertToken()
    {
        self::deleteToken($this->resourceIdentifier);
        self::$database->query("INSERT INTO token(resource_id, value) VALUES(?, ?)", [$this->resourceIdentifier, $this->value]);
    }
}