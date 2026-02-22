<?php

declare(strict_types=1);

namespace App\Dto;

use OpenApi\Attributes as OA;

class MoviePlayerDto
{
    public function __construct(
        #[OA\Property(
            type: 'array',
            items: new OA\Items(
                type: 'object',
                required: ['quality', 'playlist', 'video'],
                properties: [
                    new OA\Property(property: 'quality', type: 'string', example: '360p'),
                    new OA\Property(property: 'playlist', type: 'string', format: 'uri', example: 'https://example.com/manifest.m3u8'),
                    new OA\Property(property: 'video', type: 'string', format: 'uri', example: 'https://example.com/video.mp4'),
                ]
            )
        )]
        public readonly array $streams,
    ) {
    }
}
