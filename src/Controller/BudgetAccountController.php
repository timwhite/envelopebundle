<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\BudgetGroupRepository;
use App\Repository\TransactionRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class BudgetAccountController extends AbstractController
{
    #[Route(path: '/budgetaccounts/', name: 'envelope_budgets')]
    public function budgetAccountList(Request $request, BudgetGroupRepository $budgetGroupRepository, TransactionRepository $transactionRepository): Response
    {
        if ($request->query->get('startdate')) {
            $startDate = new \DateTime($request->query->get('startdate'));
        } else {
            $startDate = new \DateTime($transactionRepository->findUserFirstTransactionDate());
        }
        if ($request->query->get('enddate')) {
            $endDate = new \DateTime($request->query->get('enddate'));
        } else {
            $endDate = new \DateTime($transactionRepository->findUserLastTransactionDate());
        }

        return $this->render(
            'default/budgetaccounts.html.twig',
            [
                'budgetgroups' => $budgetGroupRepository->findUsersBudgetGroups(),
                'startdate' => $startDate,
                'enddate' => $endDate,
            ]
        );
    }
}
