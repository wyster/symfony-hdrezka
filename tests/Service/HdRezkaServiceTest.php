<?php
declare(strict_types=1);

namespace App\Tests\Service;

use App\Service\HdRezkaService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpClient\MockHttpClient;

class HdRezkaServiceTest extends TestCase
{

    public function testGetMovieDetails(): void
    {
        $mockHttpClient = new MockHttpClient();
        $hdRezkaService = new HdRezkaService(HttpClient::create(), 'http://iphjqlbl:q5wt4li0bzj0@185.199.229.156:7492');
        $movieDetails = $hdRezkaService->getMovieDetails(59703, 358);
        dd($movieDetails);
        $expected = json_decode(file_get_contents(__DIR__ . '/fixtures/get_movie_details_success_response.json'), true);
        self::assertArrayHasKey('success', $movieDetails);
        self::assertTrue($movieDetails['success']);
        self::assertArrayHasKey('url', $movieDetails);
    }

    public function testGetSerialDetails(): void
    {
        $hdRezkaService = new HdRezkaService(HttpClient::create());
        $movieDetails = $hdRezkaService->getSerialPlayer(64699, 59, 1, 3);
        $expected = json_decode(file_get_contents(__DIR__ . '/fixtures/get_movie_details_success_response.json'), true);
        self::assertArrayHasKey('url', $movieDetails);
    }

    public function testGetSeriesDetails(): void
    {
        $hdRezkaService = new HdRezkaService(HttpClient::create());
        $data = $hdRezkaService->getSeries(64699, 59);
        self::assertArrayHasKey('seasons', $data);
        self::assertArrayHasKey('episodes', $data);
    }

    public function testGetDetails(): void
    {
        $hdRezkaService = new HdRezkaService(HttpClient::create());
        $movieDetails = $hdRezkaService->getDetails(64961);
        self::assertTrue($movieDetails['isSerial']);
        self::assertSame('Тамбурмажоретки', $movieDetails['name']);
        self::assertNotEmpty($movieDetails['translators']);
    }

    #[DataProvider('getMethodFromUrlDataProvider')]
    public function testGetIdFromUrl(int $expected, string $url): void
    {
        self::assertSame($expected, HdRezkaService::getIdFromUrl($url));
    }

    public static function getMethodFromUrlDataProvider(): iterable
    {
        yield [64699, 'https://rezka.ag/series/drama/64699-holokost-1978.html#t:59-s:1-e:3'];
    }
}
