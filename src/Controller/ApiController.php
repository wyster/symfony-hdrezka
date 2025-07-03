<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\HdRezkaService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Annotation\Route;

class ApiController extends AbstractController
{
    #[Route('/movie/player', name: 'api_movie_player', methods: [Request::METHOD_GET])]
    public function moviePlayer(
        #[MapQueryParameter] int $id,
        #[MapQueryParameter(name: 'translator_id')] int $translatorId,
        HdRezkaService $hdRezkaService,
    ): JsonResponse {
        return $this->json($hdRezkaService->getMovieDetails($id, $translatorId));
    }

    #[Route('/serial/player', name: 'api_serial_player', methods: [Request::METHOD_GET])]
    public function serialPlayer(
        #[MapQueryParameter] int $id,
        #[MapQueryParameter(name: 'translator_id')] int $translatorId,
        #[MapQueryParameter] int $season,
        #[MapQueryParameter] int $episode,
        HdRezkaService $hdRezkaService,
    ): JsonResponse {
        return $this->json($hdRezkaService->getSerialPlayer($id, $translatorId, $season, $episode));
    }

    #[Route('/id-from-url', name: 'api_id_from_url', methods: [Request::METHOD_GET])]
    public function idFromUrl(#[MapQueryParameter] string $url): JsonResponse
    {
        $id = HdRezkaService::getIdFromUrl($url);
        if (null === $id) {
            throw $this->createNotFoundException();
        }

        return $this->json(['id' => $id]);
    }

    #[Route('/details', name: 'api_details', methods: [Request::METHOD_GET])]
    public function details(#[MapQueryParameter] int $id, HdRezkaService $hdRezkaService): JsonResponse
    {
        return $this->json($hdRezkaService->getDetails($id));
    }

    #[Route('/serial/episodes', name: 'api_serial_episodes', methods: [Request::METHOD_GET])]
    public function serialEpisodes(
        #[MapQueryParameter] int $id,
        #[MapQueryParameter(name: 'translator_id')] int $translatorId,
        HdRezkaService $hdRezkaService,
    ): JsonResponse {
        return $this->json($hdRezkaService->getSeries($id, $translatorId));
    }

    #[Route('/search', name: 'api_search', methods: [Request::METHOD_GET])]
    public function search(#[MapQueryParameter] string $q, HdRezkaService $hdRezkaService): JsonResponse
    {
        return $this->json($hdRezkaService->search($q));
    }
}
