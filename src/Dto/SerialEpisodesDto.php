<?php

declare(strict_types=1);

namespace App\Dto;

use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;

class SerialEpisodesDto
{
    public function __construct(
        #[OA\Property(
            type: 'array',
            items: new OA\Items(ref: new Model(type: SeasonDto::class)),
        )]
        public readonly array $seasons,
        #[OA\Property(
            type: 'array',
            items: new OA\Items(ref: new Model(type: EpisodeDto::class)),
        )]
        public readonly array $episodes,
    ) {
    }
}
