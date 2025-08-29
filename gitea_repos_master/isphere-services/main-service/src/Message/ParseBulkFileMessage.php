<?php

declare(strict_types=1);

namespace App\Message;

class ParseBulkFileMessage
{
    private ?int $bulkId = null;

    public function setBulkId(?int $bulkId): self
    {
        $this->bulkId = $bulkId;

        return $this;
    }

    public function getBulkId(): ?int
    {
        return $this->bulkId;
    }
}
