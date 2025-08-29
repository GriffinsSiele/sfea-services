<?php

declare(strict_types=1);

namespace App\Model;

class CountOfProcessing
{
    public function __construct(
        private readonly int $processing,
        private readonly int $totalProcessing,
    ) {
    }

    public function getProcessing(): int
    {
        return $this->processing;
    }

    public function getTotalProcessing(): int
    {
        return $this->totalProcessing;
    }
}
