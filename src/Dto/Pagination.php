<?php

declare(strict_types=1);

namespace Nektria\Dto;

use Nektria\Util\Validate;

readonly class Pagination
{
    public function __construct(
        public int $page,
        public int $maxResults,
    ) {
        Validate::pagination($this->page, $this->maxResults);
    }
}
