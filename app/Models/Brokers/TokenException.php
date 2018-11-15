<?php namespace Models\Brokers;

class TokenException extends \Exception
{
    const ERR_NOT_PROVIDED = 900;
    const ERR_INVALID_FORMAT = 901;
    const ERR_RESOURCE_NOT_FOUND = 902;
    const ERR_EXPIRED = 903;
    const ERR_DATABASE = 904;
    const ERR_INVALID_VALUE = 905;

    public function __construct($code)
    {
        parent::__construct($this->codeToMessage($code), $code);
    }

    private function codeToMessage($code)
    {
        switch ($code) {
            case self::ERR_NOT_PROVIDED:
                $message = "Token has not been provided";
                break;
            case self::ERR_INVALID_FORMAT:
                $message = "Provided token has not the proper format";
                break;
            case self::ERR_RESOURCE_NOT_FOUND:
                $message = "Token for requested resource not found";
                break;
            case self::ERR_EXPIRED:
                $message = "Token expired and thus cannot be used";
                break;
            case self::ERR_DATABASE:
                $message = "Database error occurred";
                break;
            case self::ERR_INVALID_VALUE:
                $message = "Token value does not match";
                break;
            default:
                $message = "Unknown token error";
                break;
        }
        return $message;
    }
}
