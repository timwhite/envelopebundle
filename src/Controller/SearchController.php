<?php

namespace App\Controller;

use App\Repository\TransactionRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class SearchController extends AbstractController
{
    public function __construct(private readonly TransactionRepository $transactionRepository)
    {
    }

    #[Route('/search', name: 'envelope_budget_search')]
    public function searchAction(Request $request)
    {
        $searchTerm = $request->query->get('q');

        // Find all transactions that match in description or full description
        return $this->render(
            'Search/results.html.twig',
            [
                'transactions' => $this->transactionRepository->getSearchResult($searchTerm),
                'searchterm' => $searchTerm,
            ]
        );
    }
}
