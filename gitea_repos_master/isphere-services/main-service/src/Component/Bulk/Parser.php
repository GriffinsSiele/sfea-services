<?php

declare(strict_types=1);

namespace App\Component\Bulk;

use App\Entity\Bulk;
use App\Model\FileArrayCollection;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\Mime\MimeTypeGuesserInterface;

class Parser implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    public function __construct(
        private readonly Guesser $guesser,
        private readonly MimeTypeGuesserInterface $mimeTypeGuesser,
        private readonly string $bulkpath,
        private readonly iterable $bulkParsers,
    ) {
    }

    public function parse(Bulk $bulk): void
    {
        $filename = \rtrim($this->bulkpath, '/').'/'.$bulk->getFilename();
        $mimeType = $this->mimeTypeGuesser->guessMimeType($filename);

        /** @var ParserInterface $bulkParser */
        foreach ($this->bulkParsers as $bulkParser) {
            if (!$bulkParser->isSupportsMimeType($mimeType)) {
                continue;
            }

            $this->logger->debug('Parsing file using encoder', [
                'bulk_id' => $bulk->getId(),
                'parser' => $bulkParser::class,
            ]);

            $rows = $bulkParser->parse($filename, $mimeType);
            $bulk->setRows(new FileArrayCollection($rows, $bulk->getFilename()));

            $this->logger->debug('Parsed rows', [
                'bulk_id' => $bulk->getId(),
                'rows' => \count($rows),
            ]);

            $this->logger->debug('Guess file', [
                'bulk_id' => $bulk->getId(),
            ]);

            $definitions = $this->guesser->guessMany($rows);
            $bulk->setDefinitions($definitions);

            $this->logger->debug('Guessed columns', [
                'definitions' => \count($definitions),
            ]);

            return;
        }

        throw new \InvalidArgumentException(\sprintf('Unsupported mime type "%s"', $mimeType));
    }
}
