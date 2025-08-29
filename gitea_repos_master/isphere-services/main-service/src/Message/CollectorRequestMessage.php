<?php

declare(strict_types=1);

namespace App\Message;

use App\Model\Request;

class CollectorRequestMessage
{
    public function __construct(
        private readonly Request $request,
    ) {
    }

    public function getRequest(): Request
    {
        return $this->request;
    }
}
