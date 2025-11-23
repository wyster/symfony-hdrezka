<?php

declare(strict_types=1);

namespace App\Dto;

class SeasonDto
{
    public function __construct(
        public readonly int $id,
        public readonly string $title,
    ) {
    }
}
