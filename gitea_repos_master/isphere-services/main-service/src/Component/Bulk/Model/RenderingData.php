<?php

declare(strict_types=1);

namespace App\Component\Bulk\Model;

class RenderingData
{
    public function __construct(
        private readonly array $headers,
        private readonly array $rows,
    ) {
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function getRows(): array
    {
        return $this->rows;
    }
}
