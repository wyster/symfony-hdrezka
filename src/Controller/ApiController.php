<?php

declare(strict_types=1);

namespace App\Controller;

use App\Dto\DetailsDto;
use App\Dto\MoviePlayerDto;
use App\Dto\SearchResultDto;
use App\Dto\SerialEpisodesDto;
use App\Service\HdRezkaService;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Annotation\Route;

class ApiController extends AbstractController
{
    #[OA\Response(
        response: 200,
        description: 'Returns movie player details',
        content: new OA\JsonContent(
            type: 'object',
            ref: new Model(type: MoviePlayerDto::class)
        )
    )]
    #[Route('/movie/player', name: 'api_movie_player', methods: [Request::METHOD_GET])]
    public function moviePlayer(
        #[MapQueryParameter] int $id,
        #[MapQueryParameter(name: 'translator_id')] int $translatorId,
        HdRezkaService $hdRezkaService,
    ): JsonResponse {
        return $this->json($hdRezkaService->getMoviePlayer($id, $translatorId));
    }

    #[OA\Response(
        response: 200,
        description: 'Returns movie player details',
        content: new OA\JsonContent(
            type: 'object',
            ref: new Model(type: MoviePlayerDto::class)
        )
    )]
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

    #[OA\Response(response: Response::HTTP_NOT_FOUND, description: 'ID not detected')]
    #[OA\Response(
        response: Response::HTTP_OK,
        description: 'Returns the ID',
        content: new OA\JsonContent(
            type: 'object',
            properties: [
                new OA\Property(property: 'id', type: 'integer'),
            ]
        )
    )]
    #[Route('/id-from-url', name: 'api_id_from_url', methods: [Request::METHOD_GET])]
    public function idFromUrl(#[MapQueryParameter] string $url): JsonResponse
    {
        $id = HdRezkaService::getIdFromUrl($url);
        if (null === $id) {
            throw $this->createNotFoundException();
        }

        return $this->json(['id' => $id]);
    }

    #[OA\Response(
        response: 200,
        description: 'Returns the details about movie',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(ref: new Model(type: DetailsDto::class))
        )
    )]
    #[Route('/details', name: 'api_details', methods: [Request::METHOD_GET])]
    public function details(
        #[MapQueryParameter] int $id,
        HdRezkaService $hdRezkaService,
    ): JsonResponse {
        return $this->json(
            $hdRezkaService->getDetails($id)
        );
    }

    #[OA\Response(
        response: 200,
        description: 'Returns the serial episodes',
        content: new OA\JsonContent(
            type: 'object',
            ref: new Model(type: SerialEpisodesDto::class)
        )
    )]
    #[Route('/serial/episodes', name: 'api_serial_episodes', methods: [Request::METHOD_GET])]
    public function serialEpisodes(
        #[MapQueryParameter] int $id,
        #[MapQueryParameter(name: 'translator_id')] int $translatorId,
        HdRezkaService $hdRezkaService,
    ): JsonResponse {
        return $this->json(
            $hdRezkaService->getSeries($id, $translatorId)
        );
    }

    #[OA\Response(
        response: 200,
        description: 'Success response',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(ref: new Model(type: SearchResultDto::class))
        )
    )]
    #[Route('/search', name: 'api_search', methods: [Request::METHOD_GET])]
    public function search(
        #[MapQueryParameter] string $q,
        HdRezkaService $hdRezkaService,
    ): JsonResponse {
        return $this->json($hdRezkaService->search($q));
    }
}
