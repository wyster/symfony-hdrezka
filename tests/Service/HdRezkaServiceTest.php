<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Service\HdRezkaService;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class HdRezkaServiceTest extends KernelTestCase
{
    #[DataProvider('getMethodFromUrlDataProvider')]
    public function testGetIdFromUrl(?int $expected, string $url): void
    {
        self::assertSame($expected, HdRezkaService::getIdFromUrl($url));
    }

    public static function getMethodFromUrlDataProvider(): iterable
    {
        yield [64699, 'https://rezka.ag/series/drama/64699-holokost-1978.html#t:59-s:1-e:3'];
        yield [null, 'text'];
    }
}
