<?php

declare(strict_types=1);

namespace App\Component\Bulk;

use App\Component\Bulk\Model\RenderingData;
use Symfony\Component\HttpFoundation\File\UploadedFile;

interface PrinterInterface
{
    public function printRenderingData(RenderingData $renderingData): UploadedFile;
}
