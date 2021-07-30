<?php


namespace App\Controller;

use App\Repository\TransactionRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;


class SearchController extends AbstractController
{

    /**
     * @Route (name="envelope_budget_search", path="/search/")
     *
     * @param Request $request
     *
     * @return Response
     */
    public function searchAction(Request $request, TransactionRepository $transactionRepository) {
        $searchTerm = $request->query->get('q');
        $session = $request->getSession();

        $transactions = $transactionRepository->findTextSearch("%$searchTerm%", $session->get('accessgroupid'));

        // Find all transactions that match in description or full description
        return $this->render(
            'search/results.html.twig',
            [
                'transactions' => $transactions,
                'searchterm' => $searchTerm
            ]
        );
    }
}