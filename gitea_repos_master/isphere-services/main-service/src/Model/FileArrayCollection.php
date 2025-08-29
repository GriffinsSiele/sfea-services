<?php

declare(strict_types=1);

namespace App\Model;

use Doctrine\Common\Collections\ArrayCollection;

class FileArrayCollection extends ArrayCollection
{
    private readonly string $filename;

    public function __construct(array $elements = [], string $basedOnFilename = '')
    {
        parent::__construct($elements);

        $this->filename = $basedOnFilename.'.serialized';
    }

    public function getFilename(): string
    {
        return $this->filename;
    }
}
