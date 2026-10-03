<?php
namespace carry0987\Sanite\Exceptions;

class DatabaseException extends \Exception
{
    private mixed $errorInfo;

    // Override constructor to pass error information
    public function __construct(string $message, mixed $code = 0, mixed $errorInfo = [], ?\Throwable $previous = null)
    {
        parent::__construct($message, (int) $code, $previous);
        $this->errorInfo = $errorInfo;
    }

    public static function fromPDOException(\PDOException $exception): self
    {
        return new self(
            $exception->getMessage(),
            $exception->getCode(),
            $exception->errorInfo,
            $exception
        );
    }

    public function __toString()
    {
        return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
    }

    public function getErrorInfo(): mixed
    {
        return $this->errorInfo;
    }
}
