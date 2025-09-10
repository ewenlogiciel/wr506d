<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class ProductController extends AbstractController
{
    #[Route('/products', name: 'product_list')]
    public function listProducts(): Response
    {
        return $this->render('product/list.html.twig', [
            'title' => 'Liste des produits',
        ]);
    }

    #[Route('/product/{id}', name: 'viewProduct')]
    public function viewProduct(int $id): Response
    {

        return $this->render('product/list.html.twig', [
            'title' => "Affichage du produit $id",
            'product' => $id,
        ]);
    }
}
