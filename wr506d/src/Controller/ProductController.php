<?php

namespace App\Controller;

use App\Service\SlugifyService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class ProductController extends AbstractController
{
    #[Route('/products', name: 'product_list')]
    public function listProducts(SlugifyService $slugifyService): Response
    {
        $title = 'Liste des produits';
        $slug = $slugifyService->generate("T-Shirt d'Été !");

        return $this->render('product/list.html.twig', [
            'title' => $title,
            'slug' => $slug,
        ]);
    }

    #[Route('/product/{id}', name: 'viewProduct')]
    public function viewProduct(int $id): Response
    {
        return $this->render('product/view.html.twig', [
            'title' => "Affichage du produit $id",
            'id' => $id,
        ]);


    }
}
