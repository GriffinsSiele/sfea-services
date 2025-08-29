<?php

declare(strict_types=1);

namespace App\Utils\Legacy;

class ExceptionUtilStatic
{
    public static function props(\Throwable $e): array
    {
        return [
            'exception_message' => $e->getMessage(),
            'exception_code' => $e->getCode(),
            'exception_stack_trace' => $e->getTraceAsString(),
        ];
    }
}
