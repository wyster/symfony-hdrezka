<?php

declare(strict_types=1);

namespace App\Tests\Helper;

use App\Helper\HdRezkaHelper;
use PHPUnit\Framework\TestCase;

class HdRezkaHelperTest extends TestCase
{
    public function testParseStreams(): void
    {
        $value = '[360p]https://mock.cdn/streams/360p/playlist.m3u8 or https://mock.cdn/streams/360p/video.mp4,[480p]https://mock.cdn/streams/480p/playlist.m3u8 or https://mock.cdn/streams/480p/video.mp4,[720p]https://mock.cdn/streams/720p/playlist.m3u8 or https://mock.cdn/streams/720p/video.mp4,[1080p]https://mock.cdn/streams/1080p/playlist.m3u8 or https://mock.cdn/streams/1080p/video.mp4,[1080p Ultra]https://mock.cdn/streams/1080p-ultra/playlist.m3u8 or https://mock.cdn/streams/1080p-ultra/video.mp4';
        $expected = [
            [
                'quality' => '360p',
                'playlist' => 'https://mock.cdn/streams/360p/playlist.m3u8',
                'video' => 'https://mock.cdn/streams/360p/video.mp4',
            ],
            [
                'quality' => '480p',
                'playlist' => 'https://mock.cdn/streams/480p/playlist.m3u8',
                'video' => 'https://mock.cdn/streams/480p/video.mp4',
            ],
            [
                'quality' => '720p',
                'playlist' => 'https://mock.cdn/streams/720p/playlist.m3u8',
                'video' => 'https://mock.cdn/streams/720p/video.mp4',
            ],
            [
                'quality' => '1080p',
                'playlist' => 'https://mock.cdn/streams/1080p/playlist.m3u8',
                'video' => 'https://mock.cdn/streams/1080p/video.mp4',
            ],
            [
                'quality' => '1080p Ultra',
                'playlist' => 'https://mock.cdn/streams/1080p-ultra/playlist.m3u8',
                'video' => 'https://mock.cdn/streams/1080p-ultra/video.mp4',
            ],
        ];
        self::assertSame($expected, HdRezkaHelper::parseStreams($value));
    }
}
