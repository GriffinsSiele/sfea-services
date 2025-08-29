<?php

declare(strict_types=1);

namespace App\Model;

use Symfony\Component\Validator\Constraints\Range;

class Period
{
    private ?\DateTimeInterface $from = null;

    #[Range(minPropertyPath: 'from')]
    private ?\DateTimeInterface $to = null;

    public function setFrom(?\DateTimeInterface $from): self
    {
        $this->from = $from;

        return $this;
    }

    public function getFrom(): ?\DateTimeInterface
    {
        return $this->from;
    }

    public function setTo(?\DateTimeInterface $to): self
    {
        $this->to = $to;

        return $this;
    }

    public function getTo(): ?\DateTimeInterface
    {
        return $this->to;
    }
}
