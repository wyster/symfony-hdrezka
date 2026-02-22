<?php

declare(strict_types=1);

namespace App\Helper;

class HdRezkaHelper
{
    public static function parseStreams(string $uri): array
    {
        $pattern = '/\[(\d+p(?:\sUltra)?)\]\s*(https?:\/\/[^,\s]+?)\s*or\s*(https?:\/\/[^,\s]+)(?=,|$)/i';

        preg_match_all($pattern, $uri, $matches, PREG_SET_ORDER);

        $result = [];

        foreach ($matches as $m) {
            $result[] = [
                'quality' => $m[1],
                'playlist' => $m[2],
                'video' => $m[3],
            ];
        }

        return $result;
    }
}
