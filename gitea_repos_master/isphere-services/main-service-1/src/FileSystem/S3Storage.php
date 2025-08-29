<?php

declare(strict_types=1);

namespace App\FileSystem;

use App\Environment\EnvironmentHelper;
use App\Model\S3Key;
use Aws\S3\S3Client;

class S3Storage
{
    /**
     * @var S3Storage|null
     */
    private static $instance = null;

    /**
     * @var S3Client
     */
    private $client;

    /**
     * @var array<string,string>
     */
    private $mirror = [];

    public static function getInstance(): S3Storage
    {
        if (self::$instance !== null) {
            return self::$instance;
        }

        return self::$instance = new self(new EnvironmentHelper());
    }

    public function __construct(EnvironmentHelper $environmentHelper)
    {
        $this->client = new S3Client([
            'version' => getenv('S3_VERSION'),
            'region' => getenv('S3_REGION'),
            'endpoint' => getenv('S3_ENDPOINT'),
            'use_path_style_endpoint' => (bool)getenv('S3_USE_PATH_STYLE_ENDPOINT'),
            'credentials' => [
                'key' => getenv('S3_ACCESS_KEY'),
                'secret' => getenv('S3_SECRET_KEY'),
            ],
        ]);

        foreach ($environmentHelper->getArray('S3_MIRROR') as $value) {
            $this->mirror[$value['PATH']] = $value['BUCKET_NAME'];
        }
    }

    public function isFilenameSupports(string $filename): bool
    {
        $filename = $this->cleanPath($filename);

        foreach ($this->mirror as $prefix => $_) {
            if (strpos($filename, $prefix) === 0) {
                return true;
            }
        }

        return false;
    }

    public function resourceExists(string $filename): bool
    {
        $key = $this->filenameToKey($filename);

        return $this->client->doesObjectExistV2($key->getBucket(), $key->getKey());
    }

    public function resourceGetContents(string $filename): ?string
    {
        $key = $this->filenameToKey($filename);

        return $this->client
            ->getObject([
                'Bucket' => $key->getBucket(),
                'Key' => $key->getKey(),
            ])
            ->get('Body')
            ->getContents();
    }

    public function resourcePutContents(string $filename, $contents = null): int
    {
        $key = $this->filenameToKey($filename);

        $this->client->putObject([
            'Bucket' => $key->getBucket(),
            'Key' => $key->getKey(),
            'Body' => $contents,
        ]);

        return strlen($contents);
    }

    private function cleanPath(string $filename): string
    {
        return ltrim($filename, '/.');
    }

    private function filenameToKey(string $filename): S3Key
    {
        $filename = $this->cleanPath($filename);

        foreach ($this->mirror as $prefix => $bucket) {
            if (strpos($filename, $prefix) === 0) {
                return new S3Key(
                    ltrim(substr($filename, strlen($prefix)), '/'),
                    $bucket
                );
            }
        }

        throw new \RuntimeException('could not find s3 mirror for: ' . $filename);
    }
}
