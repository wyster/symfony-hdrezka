<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\DetailsDto;
use App\Dto\EpisodeDto;
use App\Dto\MoviePlayerDto;
use App\Dto\SearchResultDto;
use App\Dto\SeasonDto;
use App\Dto\SerialEpisodesDto;
use App\Dto\TranslationDto;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpClient\Retry\GenericRetryStrategy;
use Symfony\Component\HttpClient\RetryableHttpClient;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\String\UnicodeString;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class HdRezkaService
{
    private readonly HttpClientInterface $httpClient;

    public function __construct(
        HttpClientInterface $httpClient,
        private readonly CacheInterface $cache,
        private readonly ?string $proxy = null,
    ) {
        $options = [
            'base_uri' => 'https://rezka.ag',
            'timeout' => 10,
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/121.0.0.0 Safari/537.36',
            ],
        ];
        $strategy = new GenericRetryStrategy([0, 500]);
        $this->httpClient = new RetryableHttpClient($httpClient->withOptions($options), $strategy);
    }

    public function getMoviePlayer(int $id, int $translatorId): MoviePlayerDto
    {
        $options = [
            'body' => [
                'id' => $id,
                'translator_id' => $translatorId,
                'action' => 'get_movie',
            ],
        ];
        $response = $this->httpClient->request(Request::METHOD_POST, '/ajax/get_cdn_series/?t='.time() - 1, $options);
        $data = json_decode($response->getContent(), true, flags: JSON_THROW_ON_ERROR);
        if (false === $data['success']) {
            throw new \RuntimeException($data['message']);
        }

        return new MoviePlayerDto($data['url'] ?: throw new \RuntimeException('Url is empty'));
    }

    public function getSerialPlayer(int $id, int $translatorId, int $season, int $episode): array
    {
        $options = [
            'body' => [
                'id' => $id,
                'translator_id' => $translatorId,
                'action' => 'get_stream',
                'episode' => $episode,
                'season' => $season,
            ],
        ];
        if ($this->proxy) {
            $options['proxy'] = $this->proxy;
        }
        $response = $this->httpClient->request(Request::METHOD_POST, '/ajax/get_cdn_series/?t='.time(), $options);
        $data = json_decode($response->getContent(), true, flags: JSON_THROW_ON_ERROR);
        if (false === $data['success']) {
            throw new \RuntimeException($data['message']);
        }

        return $data;
    }

    public static function getIdFromUrl(string $url): ?int
    {
        $matches = null;
        preg_match('/\d+/', $url, $matches);
        if ($matches[0] ?? null) {
            return (int) $matches[0];
        }

        return null;
    }

    public function getDetails(int $id): DetailsDto
    {
        $content = $this->cache->get('hdrezka_'.$id, function (ItemInterface $cacheItem) use ($id): string {
            $options = [
                'headers' => [
                    'Cookie' => 'dle_user_taken=1',
                ],
                'timeout' => 20,
            ];
            $response = $this->httpClient->request(Request::METHOD_GET, "/{$id}-page.html", $options);
            $cacheItem->set($response->getContent());
            $cacheItem->expiresAt((new \DateTime())->add(new \DateInterval('P1D')));

            return $response->getContent();
        });

        $dom = new Crawler($content);

        $translators = [];
        $translatorsList = $dom->filter('#translators-list')->children();
        if ($translatorsList->count() > 0) {
            $translatorsList->each(function (Crawler $item) use (&$translators) {
                $text = $item->text();
                $img = $item->filter('img');
                if ($img->count()) {
                    $text .= ' ('.$img->attr('title').')';
                }
                $translators[] = new TranslationDto(
                    (int) $item->attr('data-translator_id'),
                    $text
                );
            });
        }
        if (0 === count($translators)) {
            $matches = [];
            preg_match(sprintf('/initCDNSeriesEvents\(%s, ([0-9]+),/i', $id), $content, $matches);
            if ($defaultTranslationId = ($matches[1] ?? null)) {
                $translators[] = new TranslationDto(
                    (int) $defaultTranslationId,
                    'Default'
                );
            }
        }
        if (0 === count($translators)) {
            $matches = [];
            preg_match(sprintf('/initCDNMoviesEvents\(%s, ([0-9]+),/i', $id), $content, $matches);
            if ($defaultTranslationId = ($matches[1] ?? null)) {
                $translators[] = new TranslationDto(
                    (int) $defaultTranslationId,
                    'Default'
                );
            }
        }

        $isSerial = $dom->filter('ul.b-simple_seasons__list')->count() > 0 && $dom->filter('ul.b-simple_episodes__list')->count() > 0;
        $cover = null;
        try {
            $cover = $dom->filter('[data-imagelightbox="cover"]')->attr('href');
        } catch (\Throwable) {
        }

        $description = '';
        try {
            $description = $dom->filter('.b-post__description_text')->text();
        } catch (\Throwable) {
        }

        $year = null;
        try {
            $text = $dom->filterXPath('//h2[text()="Дата выхода"]')->closest('tr')->filter('td')->eq(1)->text();
            $year = (int) (new UnicodeString($text))->match('/[0-9]{4}/')[0];
        } catch (\Throwable) {
            // ignore
        }

        return new DetailsDto(
            $isSerial,
            $dom->filter('.b-post__title')->text(),
            $translators,
            $cover,
            $description,
            $dom->filter('.b-post__origtitle')->text(),
            $year
        );
    }

    public function getSeries(int $id, int $translatorId): SerialEpisodesDto
    {
        $response = $this->httpClient->request(Request::METHOD_POST, '/ajax/get_cdn_series/?t='.time(), [
            'body' => [
                'id' => $id,
                'translator_id' => $translatorId,
                'action' => 'get_episodes',
            ],
        ]);
        $data = json_decode($response->getContent(), true, flags: JSON_THROW_ON_ERROR);
        $seasons = [];
        $crawler = new Crawler($data['seasons']);
        foreach ($crawler->filter('li') as $item) {
            $seasons[] = new SeasonDto(
                (int) $item->attributes->getNamedItem('data-tab_id')->textContent,
                $item->textContent,
            );
        }
        $episodes = [];
        $crawler = new Crawler($data['episodes']);
        foreach ($crawler->filter('li') as $item) {
            $episodes[] = new EpisodeDto(
                $item->textContent,
                (int) $item->attributes->getNamedItem('data-season_id')->textContent,
                (int) $item->attributes->getNamedItem('data-episode_id')->textContent
            );
        }

        return new SerialEpisodesDto($seasons, $episodes);
    }

    /**
     * @return array<SearchResultDto>
     */
    public function search(string $q): array
    {
        $content = $this->cache->get('hdrezka_search'.$q, function (ItemInterface $cacheItem) use ($q): string {
            $response = $this->httpClient->request(Request::METHOD_POST, '/engine/ajax/search.php', [
                'query' => [
                    'q' => $q,
                ],
            ]);
            $cacheItem->set($response->getContent());
            $cacheItem->expiresAt((new \DateTime())->add(new \DateInterval('PT1H')));

            return $response->getContent();
        });

        $crawler = new Crawler($content);
        $results = [];
        $crawler->filter('.b-search__section_list li')->each(function (Crawler $item) use (&$results): void {
            $text = new UnicodeString((new UnicodeString($item->filter('a')->text()))->match('/\((.*)\)/')[1]);
            $results[] = new SearchResultDto(
                trim($item->filter('.enty')->text() ?: throw new \RuntimeException('Name is empty')),
                HdRezkaService::getIdFromUrl($item->filter('a')->attr('href')) ?: throw new \RuntimeException('ID is not  found'),
                trim($text->containsAny(',') ? $text->match('/^(.*?),/')[1] ?? '' : ''),
                trim($text->containsAny(',') ? explode(', ', $text->toString())[array_key_last(explode(', ', $text->toString()))] : $text->match('/(.*)$/')[1] ?? '')
            );
        });

        return $results;
    }
}
