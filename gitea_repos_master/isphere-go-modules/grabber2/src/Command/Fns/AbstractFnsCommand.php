<?php

declare(strict_types=1);

namespace App\Command\Fns;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\RequestOptions;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\OutputStyle;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Uid\Uuid;
use Symfony\Contracts\Service\Attribute\Required;

abstract class AbstractFnsCommand extends Command
{
    private ?CacheItemPoolInterface $cacheItemPool;
    private ?ClientInterface $fnsClient;
    private ?string $kernelCacheDir;

    #[Required]
    public function setCacheItemPool(?CacheItemPoolInterface $cacheItemPool): void
    {
        $this->cacheItemPool = $cacheItemPool;
    }

    #[Required]
    public function setFnsClient(?ClientInterface $fnsClient): void
    {
        $this->fnsClient = $fnsClient;
    }

    #[Required]
    public function setKernelCacheDir(?string $kernelCacheDir): void
    {
        $this->kernelCacheDir = $kernelCacheDir;
    }

    protected function fetchPage(OutputStyle $io, string $url): string
    {
        $page = $this->cacheItemPool->getItem(\md5($url));

        if (!$page->isHit()) {
            $io->info('Fetching page: '.$url);

            $response = $this->fnsClient->request(Request::METHOD_GET, $url);

            if (Response::HTTP_OK !== $response->getStatusCode()) {
                throw new \RuntimeException('Unexpected page status code: '.$response->getStatusCode());
            }

            $this->cacheItemPool->save(
                $page->set($response->getBody()->getContents()),
            );
        }

        $content = (new Crawler($page->get()))
            ->filterXPath('//div[contains(@property, "dc:source")]')
            ->attr('content');

        if (empty($content)) {
            throw new \RuntimeException('Empty dc:source content');
        }

        return $content;
    }

    protected function fetchSource(OutputStyle $io, string $url, string $expectedExtension, bool $useCache): string
    {
        $extension = \pathinfo($url, \PATHINFO_EXTENSION);

        if ($expectedExtension !== $extension) {
            throw new \RuntimeException('Unexpected file extension of dc:source content: '.$extension);
        }

        $filename = $this->kernelCacheDir.\DIRECTORY_SEPARATOR.\md5($url).'.'.$extension;

        if ($useCache && \file_exists($filename)) {
            return $filename;
        }

        $io->info('Fetching source: '.$url.', local: '.$filename);

        $this->fnsClient->request(Request::METHOD_GET, $url, [
            RequestOptions::SINK => $filename,
        ]);

        return $filename;
    }

    protected function forEveryFileInArchive(string $filename, string $expectedExtension): \Generator
    {
        $archive = new \ZipArchive();

        if (true !== ($errCode = $archive->open($filename, \ZipArchive::RDONLY))) {
            throw new \RuntimeException('Cannot open archive, code: '.$errCode);
        }

        for ($i = 0; $i < $archive->numFiles; ++$i) {
            if (false === ($name = $archive->getNameIndex($i))) {
                throw new \RuntimeException('Unable to read file in archive by index: '.$i);
            }

            if (\str_ends_with($name, \DIRECTORY_SEPARATOR)) {
                continue;
            }

            $extension = \pathinfo($name, \PATHINFO_EXTENSION);

            if ($extension !== $expectedExtension) {
                throw new \RuntimeException('Archive filename '.$name.' have unexpected extension');
            }

            yield $archive->getFromIndex($i);
        }

        $archive->close();
    }

    protected function pgInsertByCopy($pdo, string $schema, string $tableName, array $fields, iterable $records)
    {
        static $delimiter = "\t", $nullAs = '\\N';

        $rows = [];

        foreach ($records as $record) {
            $record = \array_map(
                static function ($field) use ($record, $delimiter, $nullAs) {
                    $value = $record[$field] ?? null;

                    if (null === $value) {
                        $value = $nullAs;
                    } elseif (\is_bool($value)) {
                        $value = $value ? 't' : 'f';
                    } elseif ($value instanceof Uuid) {
                        $value = $value->toRfc4122();
                    } elseif (\is_int($value)) {
                        $value = (string) $value;
                    } elseif (\is_float($value)) {
                        $value = (string) $value;
                    }

                    $value = \str_replace($delimiter, ' ', $value);
                    $value = \addcslashes($value, "\0..\37");

                    return $value;
                },
                $fields
            );

            $rows[] = \implode($delimiter, $record)."\n";
        }

        return $pdo->pgsqlCopyFromArray($schema.'.'.$tableName, $rows, $delimiter, \addslashes($nullAs), \implode(',', $fields));
    }
}
