<?php

declare(strict_types=1);

namespace App\Dto;

class MoviePlayerDto
{
    public function __construct(
        public readonly string $url,
    ) {
    }
}
