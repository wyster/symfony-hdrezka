<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Dto\SearchResultDto;
use App\Service\HdRezkaService;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Contracts\Cache\CacheInterface;

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

    public function testSearch(): void
    {
        $httpClient = new MockHttpClient([
            new MockResponse((string) file_get_contents(__DIR__ .'/fixtures/search_success.html'))
        ]);
        $hdRezkaService = new HdRezkaService(
            $httpClient,
            self::createMock(CacheInterface::class)
        );
        $results = $hdRezkaService->search('test');
        self::assertCount(5, $results);
        foreach ($results as $result) {
            self::assertInstanceOf(SearchResultDto::class, $result);
        }
        self::assertSame('Завещание', $results[0]->name);
        self::assertSame('Testament', $results[0]->originalName);
        self::assertSame(1983, $results[0]->year);
        self::assertSame(21212, $results[0]->id);
    }
}
