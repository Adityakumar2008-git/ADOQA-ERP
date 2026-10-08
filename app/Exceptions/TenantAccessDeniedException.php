<?php

namespace App\Exceptions;

use Symfony\Component\HttpKernel\Exception\HttpException;

class TenantAccessDeniedException extends HttpException
{
    public function __construct(string $message = 'Access to this business is not permitted.', ?\Throwable $previous = null, int $code = 0)
    {
        parent::__construct(403, $message, $previous, [], $code);
    }
}
