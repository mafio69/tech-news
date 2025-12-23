<?php

namespace App\Controller;

use App\Repository\NewsRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class NewsController extends AbstractController
{
    #[Route('/api/news', name: 'api_news', methods: ['GET'])]
    public function index(NewsRepository $newsRepository): JsonResponse
    {
        $news = $newsRepository->findBy([], ['createdAt' => 'DESC'], 20);

        return $this->json($news);
    }
}
