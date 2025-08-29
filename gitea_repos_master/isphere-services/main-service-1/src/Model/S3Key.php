<?php

declare(strict_types=1);

namespace App\Model;

class S3Key
{
    /**
     * @var string
     */
    private $key;

    /**
     * @var string
     */
    private $bucket;

    public function __construct(string $key, string $bucket)
    {
        $this->key = $key;
        $this->bucket = $bucket;
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function getBucket(): string
    {
        return $this->bucket;
    }
}
