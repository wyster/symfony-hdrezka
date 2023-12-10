<?php

namespace App\Controller;

use App\Service\HdRezkaService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class ApiController extends AbstractController
{
    #[Route('/movie/player', name: 'api_movie_player', methods: [Request::METHOD_GET])]
    public function moviePlayer(Request $request, HdRezkaService $hdRezkaService): JsonResponse
    {
        $id = $request->query->getInt('id');
        $translatorId = $request->query->getInt('translator_id');

        return $this->json($hdRezkaService->getMovieDetails($id, $translatorId));
    }

    #[Route('/serial/player', name: 'api_serial_player', methods: [Request::METHOD_GET])]
    public function serialPlayer(Request $request, HdRezkaService $hdRezkaService): JsonResponse
    {
        $id = $request->query->getInt('id');
        $translatorId = $request->query->getInt('translator_id');

        return $this->json($hdRezkaService->getSerialPlayer(
            $id,
            $translatorId,
            $request->query->getInt('season'),
            $request->query->getInt('episode')
        ));
    }

    #[Route('/id-from-url', name: 'api_id_from_url', methods: [Request::METHOD_GET])]
    public function idFromUrl(Request $request): JsonResponse
    {
        return $this->json(['id' => HdRezkaService::getIdFromUrl($request->get('url'))]);
    }
}
