<?php


declare(strict_types=1);

namespace App\Service;

use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class HdRezkaService
{
    private readonly HttpClientInterface $httpClient;

    public function __construct(
        HttpClientInterface $httpClient,
        private readonly ?string $proxy = null
    )
    {
        $options = [
            'base_uri' => 'https://rezka.ag',
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/119.0.0.0 Safari/537.36'
            ],
            'timeout' => 10
        ];
        if ($this->proxy) {
            $options['proxy'] = $this->proxy;
        }
        $this->httpClient = $httpClient->withOptions($options);
    }

    public function getMovieDetails(int $id, int $translatorId): array
    {
        $options = [
            'body' => [
                'id' => $id,
                'translator_id' => $translatorId,
                'action' => 'get_movie'
            ]
        ];
        $response = $this->httpClient->request(Request::METHOD_POST, '/ajax/get_cdn_series/?t=' . time(), $options);
        return json_decode($response->getContent(), true, flags: JSON_THROW_ON_ERROR);
    }

    public function getSerialPlayer(int $id, int $translatorId, int $season,  int $episode): array
    {
        $options = [
            'body' => [
                'id' => $id,
                'translator_id' => $translatorId,
                'action' => 'get_stream',
                'episode' => $episode,
                'season' => $season
            ],
        ];
        if ($this->proxy) {
            $options['proxy'] = $this->proxy;
        }
        $response = $this->httpClient->request(Request::METHOD_POST, '/ajax/get_cdn_series/?t=' . time(), $options);
        return json_decode($response->getContent(), true, flags: JSON_THROW_ON_ERROR);
    }

    public static function getIdFromUrl(string $url): int
    {
        $matches = null;
        preg_match('/\d+/', $url, $matches);
        return (int) $matches[0];
    }

    public function getDetails(int $id): array
    {
        $options = [];
        if ($this->proxy) {
            $options['proxy'] = $this->proxy;
        }
        $response = $this->httpClient->request(Request::METHOD_GET, "/{$id}-page.html", $options);
        $content = $response->getContent();

        $dom = new Crawler($content);

        $translators = [];
        if ($dom->filter('#translators-list')->count()) {
            foreach ($dom->filter('#translators-list')->children() as $item) {
                $translators[] = [
                    'title' => $item->textContent,
                    'id' => (int)  $item->attributes->getNamedItem('data-translator_id')->textContent
                ];
            }
        }

        $isSerial = $dom->filter('ul.b-simple_seasons__list')->count() > 0 && $dom->filter('ul.b-simple_episodes__list')->count() > 0;

        return [
            'isSerial' => $isSerial,
            'name' => $dom->filter('.b-post__title')->text(),
            'translators' => $translators
        ];
    }

    public function getSeries(int $id, int $translatorId): array
    {
        $response = $this->httpClient->request(Request::METHOD_POST, "/ajax/get_cdn_series/?t=" . time(), [
            'body' => [
                'id' => $id,
                'translator_id' => $translatorId,
                'action' => 'get_episodes'
            ]
        ]);
        $data = json_decode($response->getContent(), true, flags: JSON_THROW_ON_ERROR);
        $seasons = [];
        $crawler = new Crawler($data['seasons']);
        foreach ($crawler->filter('li') as $item) {
            $seasons[] = [
                'title' => $item->textContent,
                'id' => (int) $item->attributes->getNamedItem('data-tab_id')->textContent
            ];
        }
        $episodes = [];
        $crawler = new Crawler($data['episodes']);
        foreach ($crawler->filter('li') as $item) {
            $episodes[] = [
                'title' => $item->textContent,
                'episode' => (int) $item->attributes->getNamedItem('data-episode_id')->textContent,
                'season' => (int) $item->attributes->getNamedItem('data-season_id')->textContent
            ];
        }

        return [
            'seasons' => $seasons,
            'episodes' => $episodes
        ];
    }
}
