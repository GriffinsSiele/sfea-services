<?php

declare(strict_types=1);

namespace App\Component\Bulk;

use App\Entity\Bulk;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class Printer implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    public function __construct(
        private readonly Renderer $renderer,
        private readonly iterable $bulkPrinters,
    ) {
    }

    public function printBulk(Bulk $bulk): ?UploadedFile
    {
        $renderingData = $this->renderer->render($bulk);

        /** @var PrinterInterface $bulkPrinter */
        foreach ($this->bulkPrinters as $bulkPrinter) {
            return $bulkPrinter->printRenderingData($renderingData);
        }

        return null;
    }
}
