<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpClient\Retry\GenericRetryStrategy;
use Symfony\Component\HttpClient\RetryableHttpClient;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class HdRezkaService
{
    private readonly HttpClientInterface $httpClient;

    public function __construct(
        HttpClientInterface $httpClient,
        private readonly ?string $proxy = null,
        private readonly CacheInterface $cache,
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

    public function getMovieDetails(int $id, int $translatorId): array
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

        return $data;
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

    public function getDetails(int $id): array
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
        if ($dom->filter('#translators-list')->count()) {
            foreach ($dom->filter('#translators-list')->children() as $item) {
                $translators[] = [
                    'title' => $item->textContent,
                    'id' => (int) $item->attributes->getNamedItem('data-translator_id')->textContent,
                ];
            }
        }
        if (0 === count($translators)) {
            $matches = [];
            preg_match(sprintf('/initCDNSeriesEvents\(%s, ([0-9]+),/i', $id), $content, $matches);
            if ($defaultTranslationId = ($matches[1] ?? null)) {
                $translators[] = [
                    'id' => (int) $defaultTranslationId,
                    'title' => 'Default',
                ];
            }
        }
        if (0 === count($translators)) {
            $matches = [];
            preg_match(sprintf('/initCDNMoviesEvents\(%s, ([0-9]+),/i', $id), $content, $matches);
            if ($defaultTranslationId = ($matches[1] ?? null)) {
                $translators[] = [
                    'id' => (int) $defaultTranslationId,
                    'title' => 'Default',
                ];
            }
        }

        $isSerial = $dom->filter('ul.b-simple_seasons__list')->count() > 0 && $dom->filter('ul.b-simple_episodes__list')->count() > 0;
        $cover = null;
        try {
            $cover = $dom->filter('[data-imagelightbox="cover"]')->attr('href');
        } catch (\Throwable) {
        }

        $description = null;
        try {
            $description = $dom->filter('.b-post__description_text')->text();
        } catch (\Throwable) {
        }

        return [
            'isSerial' => $isSerial,
            'name' => $dom->filter('.b-post__title')->text(),
            'translators' => $translators,
            'poster' => $cover,
            'description' => $description,
        ];
    }

    public function getSeries(int $id, int $translatorId): array
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
            $seasons[] = [
                'title' => $item->textContent,
                'id' => (int) $item->attributes->getNamedItem('data-tab_id')->textContent,
            ];
        }
        $episodes = [];
        $crawler = new Crawler($data['episodes']);
        foreach ($crawler->filter('li') as $item) {
            $episodes[] = [
                'title' => $item->textContent,
                'episode' => (int) $item->attributes->getNamedItem('data-episode_id')->textContent,
                'season' => (int) $item->attributes->getNamedItem('data-season_id')->textContent,
            ];
        }

        return [
            'seasons' => $seasons,
            'episodes' => $episodes,
        ];
    }

    public function search(string $q): array
    {
        $response = $this->httpClient->request(Request::METHOD_POST, '/engine/ajax/search.php', [
            'query' => [
                'q' => $q,
            ],
        ]);
        $crawler = new Crawler($response->getContent());
        $results = [];
        $crawler->filter('.b-search__section_list li')->each(function (Crawler $item) use (&$results): void {
            $results[] = [
                'name' => $item->filter('.enty')->text(),
                'url' => $item->filter('a')->attr('href'),
            ];
        });

        return $results;
    }
}
