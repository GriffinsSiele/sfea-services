<?php

declare(strict_types=1);

namespace App\Model;

trait PaginationTrait
{
    private ?int $page = null;

    private ?int $limit = null;

    public function setPage(?int $page): self
    {
        $this->page = $page;

        return $this;
    }

    public function getPage(): ?int
    {
        return $this->page;
    }

    public function setLimit(?int $limit): self
    {
        $this->limit = $limit;

        return $this;
    }

    public function getLimit(): ?int
    {
        return $this->limit;
    }
}
