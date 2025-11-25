<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Dto\SearchResultDto;
use App\Service\HdRezkaService;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Cache\Adapter\NullAdapter;
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
            new NullAdapter()
        );
        $results = $hdRezkaService->search('test');
        self::assertCount(5, $results);
        foreach ($results as $result) {
            self::assertInstanceOf(SearchResultDto::class, $result);
        }
        self::assertSame('Завещание', $results[0]->name);
        self::assertSame('Testament', $results[0]->originalName);
        self::assertSame('1983', $results[0]->year);
        self::assertSame(21212, $results[0]->id);

        self::assertSame('Тестостерон', $results[4]->name);
        self::assertSame('Testo', $results[4]->originalName);
        self::assertSame('2024-2025', $results[4]->year);
        self::assertSame(79724, $results[4]->id);
    }

    public function testSearch2(): void
    {
        $httpClient = new MockHttpClient([
            new MockResponse((string) file_get_contents(__DIR__ .'/fixtures/search_success2.html'))
        ]);
        $hdRezkaService = new HdRezkaService(
            $httpClient,
            new NullAdapter()
        );
        $results = $hdRezkaService->search('test');
        self::assertCount(1, $results);
        foreach ($results as $result) {
            self::assertInstanceOf(SearchResultDto::class, $result);
        }
        self::assertSame('Король Талсы', $results[0]->name);
        self::assertSame('Tulsa King', $results[0]->originalName);
        self::assertSame('2022 - ...', $results[0]->year);
        self::assertSame(51876, $results[0]->id);
    }

    public function testSearch3(): void
    {
        $httpClient = new MockHttpClient([
            new MockResponse((string) file_get_contents(__DIR__ .'/fixtures/search_success3.html'))
        ]);
        $hdRezkaService = new HdRezkaService(
            $httpClient,
            new NullAdapter()
        );
        $results = $hdRezkaService->search('test');
        self::assertCount(5, $results);
        foreach ($results as $result) {
            self::assertInstanceOf(SearchResultDto::class, $result);
        }
        self::assertSame('Зелёная граница', $results[0]->name);
        self::assertSame('Frontera Verde / Green Frontier', $results[0]->originalName);
        self::assertSame('2019', $results[0]->year);
        self::assertSame(31664, $results[0]->id);

        self::assertSame('Зеленая карета', $results[3]->name);
        self::assertSame('', $results[3]->originalName);
        self::assertSame('2015', $results[3]->year);
        self::assertSame(2522, $results[3]->id);
    }
}
