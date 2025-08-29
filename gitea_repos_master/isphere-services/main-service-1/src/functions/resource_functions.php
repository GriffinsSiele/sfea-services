<?php

declare(strict_types=1);

use App\FileSystem\S3Storage;
use OpenTracing\GlobalTracer;

function resource_exists(string $filename): bool
{
    $tracer = GlobalTracer::get();

    $scope = $tracer->startActiveSpan('Check resource exists');
    $scope->getSpan()->log(['filename' => $filename]);

    $s3 = S3Storage::getInstance();
    $result = null;

    if ($s3->isFilenameSupports($filename)) {
        $scope->getSpan()->setTag('s3', true);

        try {
            $exists = $s3->resourceExists($filename);
            if (!$exists) {
                throw new \RuntimeException('manually thrown exception for resource exists if false');
            }

            $result = true;
        } catch (\Throwable $e) {
            $scope->getSpan()->setTag('error', true);
            $scope->getSpan()->log(['error' => $e->getMessage()]);

            log_error(sprintf('failed to check resource exists with s3: %s', $e->getMessage()), [
                'filename' => $filename,
                'exception' => $e,
            ]);
        }
    }

    if ($result === null) {
        $result = file_exists($filename);
    }

    $scope->getSpan()->log(['exists' => $result]);

    $scope->close();

    return $result;
}

function resource_get_contents(string $filename, bool $useIncludePath = false, $context = null, int $offset = 0, ?int $length = 0)
{
    $tracer = GlobalTracer::get();

    $scope = $tracer->startActiveSpan('Get resource contents');
    $scope->getSpan()->log(['filename' => $filename]);

    $s3 = S3Storage::getInstance();
    $result = null;

    if ($s3->isFilenameSupports($filename)) {
        $scope->getSpan()->setTag('s3', true);

        try {
            $result = $s3->resourceGetContents($filename);
        } catch (\Throwable $e) {
            $scope->getSpan()->setTag('error', true);
            $scope->getSpan()->log(['error', $e->getMessage()]);

            log_error(sprintf('failed to get resource contents with s3: %s', $e->getMessage()), [
                'filename' => $filename,
                'exception' => $e,
            ]);
        }
    }

    if ($result === null) {
        $result = file_get_contents($filename);
    }

    if (is_countable($result)) {
        $scope->getSpan()->log(['filesize' => $result]);
    }

    $scope->close();

    return $result;
}

function resource_put_contents(string $filename, $data = null, int $flags = 0, $context = null)
{
    $tracer = GlobalTracer::get();

    $scope = $tracer->startActiveSpan('Put resource contents');
    $scope->getSpan()->log(['filename' => $filename]);

    if (is_countable($data)) {
        $scope->getSpan()->log(['filesize' => $data]);
    }

    $s3 = S3Storage::getInstance();

    if ($s3->isFilenameSupports($filename)) {
        $scope->getSpan()->setTag('s3', true);

        try {
            // no return for duplicate native file_put_contents below
            $s3->resourcePutContents($filename, $data);
        } catch (\Throwable $e) {
            $scope->getSpan()->setTag('error', true);
            $scope->getSpan()->log(['error' => $e->getMessage()]);

            log_error(sprintf('failed to put resource contents with s3: %s', $e->getMessage()), [
                'filename' => $filename,
                'data' => $data,
                'exception' => $e,
            ]);
        }
    }

    $result = file_put_contents($filename, $data);

    $scope->close();

    return $result;
}
