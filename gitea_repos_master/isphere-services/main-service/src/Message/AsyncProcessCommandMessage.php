<?php

declare(strict_types=1);

namespace App\Message;

class AsyncProcessCommandMessage
{
    private ?int $bulkId = null;

    public function __construct(
        private readonly int $userId,
        private readonly string $clientIp,
        private readonly int|string $reqId,
        private readonly string $xml,
    ) {
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getClientIp(): string
    {
        return $this->clientIp;
    }

    public function getReqId(): int|string
    {
        return $this->reqId;
    }

    public function getXml(): string
    {
        return $this->xml;
    }

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
