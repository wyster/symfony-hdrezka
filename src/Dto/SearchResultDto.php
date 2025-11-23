<?php

declare(strict_types=1);

namespace App\Dto;

class SearchResultDto
{
    public function __construct(
        public readonly string $name,
        public readonly int $id,
    ) {
    }
}
