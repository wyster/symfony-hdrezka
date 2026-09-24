<?php

namespace App\Tests\Helper;

use App\Helper\HdRezkaHelper;
use PHPUnit\Framework\TestCase;

class HdRezkaHelperTest extends TestCase
{
    /**
     * Test case for basic successful stream parsing.
     */
    public function testParseStreamsSuccessBasic(): void
    {
        $uri = '[1080p] https://example.com/playlist_video or https://example.com/video';

        $result = HdRezkaHelper::parseStreams($uri);

        $expected = [
            [
                'quality' => '1080p',
                'playlist' => 'https://example.com/playlist_video',
                'video' => 'https://example.com/video',
            ],
        ];

        static::assertEquals($expected, $result);
    }

    /**
     * Test case for multiple streams in a single URI string.
     */
    public function testParseStreamsSuccessMultiple(): void
    {
        $uri = '[480p] https://example.com/low_playlist or https://example.com/video,[720p Ultra]https://another.com/playlist_ultra or https://another.com/video';

        $result = HdRezkaHelper::parseStreams($uri);

        $expected = [
            [
                'quality' => '480p',
                'playlist' => 'https://example.com/low_playlist',
                'video' => 'https://example.com/video',
            ],
            [
                'quality' => '720p Ultra',
                'playlist' => 'https://another.com/playlist_ultra',
                'video' => 'https://another.com/video',
            ],
        ];

        static::assertEquals($expected, $result);
    }

    /**
     * Test case for an empty or malformed URI that should result in no matches.
     */
    public function testParseStreamsEmptyOrMalformed(): void
    {
        // Empty string
        $uri1 = '';
        static::assertEquals([], HdRezkaHelper::parseStreams($uri1));

        // Malformed string without correct structure
        $uri2 = 'This is not a stream URI';
        static::assertEquals([], HdRezkaHelper::parseStreams($uri2));
    }

    /**
     * Test case for an URI containing only one stream definition (edge case).
     */
    public function testParseStreamsSingleStream(): void
    {
        // Only a single match, no trailing comma to confirm end of line/string
        $uri = '[1080p] https://example.com/playlist or https://example.com/video';

        $result = HdRezkaHelper::parseStreams($uri);

        $expected = [
            [
                'quality' => '1080p',
                'playlist' => 'https://example.com/playlist',
                'video' => 'https://example.com/video',
            ],
        ];

        static::assertEquals($expected, $result);
    }
}
