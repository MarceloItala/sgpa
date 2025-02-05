<?php
declare(strict_types=1);

namespace SGPA\Exceptions;

class TenantException extends \Exception
{
    public function __construct(string $message = "", array $errors = [], int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
