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

class HdRezkaServiceTest extends KernelTestCase
{
    private MockHttpClient $httpClient;

    public function setUp(): void
    {
        $this->httpClient = new MockHttpClient();
    }

    private function createHdRezkaService(): HdRezkaService
    {
        return new HdRezkaService(
            $this->httpClient,
            new NullAdapter(),
        );
    }

    #[DataProvider('getMethodFromUrlDataProvider')]
    public function testGetIdFromUrl(?int $expected, string $url): void
    {
        static::assertSame($expected, HdRezkaService::getIdFromUrl($url));
    }

    /**
     * @return iterable<array{int|null, string}>
     */
    public static function getMethodFromUrlDataProvider(): iterable
    {
        yield [64699, 'https://rezka.ag/series/drama/64699-holokost-1978.html#t:59-s:1-e:3'];
        yield [null, 'text'];
    }

    public function testSearch(): void
    {
        $this->httpClient->setResponseFactory([
            new MockResponse((string) file_get_contents(__DIR__ . '/fixtures/search_success.html')),
        ]);
        $results = $this->createHdRezkaService()->search('test');
        static::assertCount(5, $results);
        foreach ($results as $result) {
            static::assertInstanceOf(SearchResultDto::class, $result); // @phpstan-ignore-line
        }
        static::assertSame('Завещание', $results[0]->name);
        static::assertSame('Testament', $results[0]->originalName);
        static::assertSame('1983', $results[0]->year);
        static::assertSame(21212, $results[0]->id);

        static::assertSame('Тестостерон', $results[4]->name);
        static::assertSame('Testo', $results[4]->originalName);
        static::assertSame('2024-2025', $results[4]->year);
        static::assertSame(79724, $results[4]->id);
    }

    public function testSearch2(): void
    {
        $this->httpClient->setResponseFactory([
            new MockResponse((string) file_get_contents(__DIR__ . '/fixtures/search_success2.html')),
        ]);
        $results = $this->createHdRezkaService()->search('test');
        static::assertCount(1, $results);
        foreach ($results as $result) {
            static::assertInstanceOf(SearchResultDto::class, $result); // @phpstan-ignore-line
        }
        static::assertSame('Король Талсы', $results[0]->name);
        static::assertSame('Tulsa King', $results[0]->originalName);
        static::assertSame('2022 - ...', $results[0]->year);
        static::assertSame(51876, $results[0]->id);
    }

    public function testSearch3(): void
    {
        $this->httpClient->setResponseFactory([
            new MockResponse((string) file_get_contents(__DIR__ . '/fixtures/search_success3.html')),
        ]);
        $results = $this->createHdRezkaService()->search('test');
        static::assertCount(5, $results);
        foreach ($results as $result) {
            static::assertInstanceOf(SearchResultDto::class, $result); // @phpstan-ignore-line1
        }
        static::assertSame('Зелёная граница', $results[0]->name);
        static::assertSame('Frontera Verde / Green Frontier', $results[0]->originalName);
        static::assertSame('2019', $results[0]->year);
        static::assertSame(31664, $results[0]->id);

        static::assertSame('Зеленая карета', $results[3]->name);
        static::assertSame('', $results[3]->originalName);
        static::assertSame('2015', $results[3]->year);
        static::assertSame(2522, $results[3]->id);
    }

    public function testSerialDetailsSuccess(): void
    {
        $this->httpClient->setResponseFactory([
            new MockResponse((string) file_get_contents(__DIR__ . '/fixtures/serial_details_success.html')),
        ]);
        $result = $this->createHdRezkaService()->getDetails(1763);
        static::assertTrue($result->isSerial);
        static::assertSame('Футурама', $result->name);
        static::assertCount(20, $result->translators);
        static::assertSame(568, $result->translators[16]->id);
        static::assertSame('Simpsonsua (Украинский)', $result->translators[16]->title);
        static::assertSame('https://static.hdrezka.ac/i/2025/8/22/y56bd9b3b39eevr28k21t.jpg', $result->poster);
        static::assertSame('Накануне Нового 2000 года, неудачливый нью-йоркский разносчик пиццы Филип Джей Фрай, которого только что бросила девушка, доставляет заказ в инновационную криогенную лабораторию, но понимает, что его разыграли. По неосторожности он случайно попадает в камеру заморозки и приходит в сознание только 31 декабря 2999 года, оказавшись в футуристическом мире. Всегда мечтавший о космических путешествиях, главный герой нанимается на работу в «Межпланетный экспресс» к своему далекому потомку, 160-летнему Хьюберту Фарнсворту, где ему составляют компанию Туранга Лила и робот Бендер.', $result->description);
        static::assertSame(1999, $result->year);
    }

    public function testMovieDetailsSuccess(): void
    {
        $this->httpClient->setResponseFactory([
            new MockResponse((string) file_get_contents(__DIR__ . '/fixtures/movie_details_success.html')),
        ]);
        $result = $this->createHdRezkaService()->getDetails(833);
        static::assertFalse($result->isSerial);
        static::assertSame('Один дома', $result->name);
        static::assertCount(29, $result->translators);
        static::assertSame('https://static.hdrezka.ac/i/2020/11/9/jfd0fa10689d2bq15x46m.jpeg', $result->poster);
        static::assertSame('Огромное семейство Маккалистеров собирается отправиться на Рождественские праздники в Париж. Как обычно это бывает, с утра оказывается, что они проспали, и все впопыхах начинают собираться. Им с большим трудом удается успеть на самолет, и когда тот уже взлетает, мать и отец восьмилетнего Кевина вспоминают, что они забыли своего младшего сына дома. В это время юный Кевин, осознав, что он остался дома сам, решает не тратить времени зря и как следует повеселиться. В то время, как его мать не находит себе места и ищет способ вернуться домой, мальчишка делает все, что его душе угодно. Однако в скором времени Кевин узнает, что парочка бандитов хочет ограбить его дом. Грабители даже не догадываются с чем им придется столкнуться, ведь мальчик как следует подготовился к их приходу.', $result->description);
        static::assertSame(1990, $result->year);
    }

    public function testMovieDetailsSuccess3(): void
    {
        $this->httpClient->setResponseFactory([
            new MockResponse((string) file_get_contents(__DIR__ . '/fixtures/movie_details_success2.html')),
        ]);
        $result = $this->createHdRezkaService()->getDetails(83735);
        static::assertFalse($result->isSerial);
        static::assertSame('Дьявол носит Prada 2', $result->name);
        static::assertCount(0, $result->translators);
        static::assertSame('https://static.hdrezka.ac/i/2026/3/13/k86eb2a4fd89bik31b12p.jpg', $result->poster);
        static::assertSame('История борьбы Миранды Пристли с Эмили Чарлтон, ее бывшей помощницей, ставшей соперницей-исполнительным директором, поскольку они конкурируют за доходы от рекламы в условиях упадка печатных СМИ, в то время как Миранда близится к выходу на пенсию.', $result->description);
        static::assertSame(2026, $result->year);
    }
}
