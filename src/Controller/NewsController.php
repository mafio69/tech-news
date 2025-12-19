<?php
namespace App\Controller;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\NewsRepository;

class NewsController extends AbstractController
{
    #[Route('/news', name: 'app_news')]
    public function index(NewsRepository $newsRepository): Response
    {
        $news = $newsRepository->findAll();
        return $this->render('news/index.html.twig', ['news' => $news]);
    }
}
