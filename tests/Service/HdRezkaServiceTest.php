<?php
declare(strict_types=1);

namespace App\Tests\Service;

use App\Service\HdRezkaService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Contracts\Cache\CacheInterface;

class HdRezkaServiceTest extends KernelTestCase
{
    private ?HdRezkaService $hdRezkaService = null;

    public function setUp(): void
    {
        parent::setUp();
        self::bootKernel();
        $proxy = self::$kernel->getContainer()->getParameter('proxy') ?: null;
        $this->hdRezkaService = new HdRezkaService(HttpClient::create(), $proxy, self::getContainer()->get(CacheInterface::class));
    }

    public function testGetMovieDetails(): void
    {
        $movieDetails = $this->hdRezkaService->getMovieDetails(59703, 358);
        $expected = json_decode(file_get_contents(__DIR__ . '/fixtures/get_movie_details_success_response.json'), true);
        self::assertArrayHasKey('success', $movieDetails);
        self::assertTrue($movieDetails['success']);
        self::assertArrayHasKey('url', $movieDetails);
    }

    public function testGetSerialDetails(): void
    {
        $movieDetails = $this->hdRezkaService->getSerialPlayer(64699, 59, 1, 3);
        $expected = json_decode(file_get_contents(__DIR__ . '/fixtures/get_movie_details_success_response.json'), true);
        self::assertArrayHasKey('url', $movieDetails);
    }

    public function testGetSeriesDetails(): void
    {
        $data = $this->hdRezkaService->getSeries(64699, 59);
        self::assertArrayHasKey('seasons', $data);
        self::assertArrayHasKey('episodes', $data);
    }

    public function testGetDetails(): void
    {
        $movieDetails = $this->hdRezkaService->getDetails(64961);
        self::assertTrue($movieDetails['isSerial']);
        self::assertSame('Тамбурмажоретки', $movieDetails['name']);
        self::assertNotEmpty($movieDetails['translators']);
        self::assertSame('https://static.hdrezka.ac/i/2023/12/9/u7d923fa73316bq67e23u.png', $movieDetails['poster']);
        self::assertSame('Присоединяясь к школьной группе поддержки, новенькая сталкивается с множеством проблем: сплетнями, предательством… и убийствами.', $movieDetails['description']);
        $movieDetails = $this->hdRezkaService->getDetails(64961);
    }

    public function testGetDetailsWithOneTranslator(): void
    {
        $movieDetails = $this->hdRezkaService->getDetails(55382);
        self::assertTrue($movieDetails['isSerial']);
        self::assertSame('Крушение', $movieDetails['name']);
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

    public function testSearch(): void
    {
        $result = $this->hdRezkaService->search('futurama');
        self::assertCount(5, $result);
        self::assertSame($result[0]['name'], 'Футурама');
        self::assertSame($result[0]['url'], 'https://rezka.ag/cartoons/fiction/1763-futurama-1999.html');
    }
}
