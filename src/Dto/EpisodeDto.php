<?php

declare(strict_types=1);

namespace App\Dto;

class EpisodeDto
{
    public function __construct(
        public readonly string $title,
        public readonly int $season,
        public readonly int $episode,
    ) {
    }
}
