<?php

namespace App\Controller;

use App\Repository\BudgetAccountRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class BudgetTransactionController extends AbstractController
{
    public function __construct(private EntityManagerInterface $em)
    {
    }

    #[Route('/budgettransactions/{accountid}', name: 'envelope_budgettransactions')]
    public function budgetTransactionList(BudgetAccountRepository $budgetAccountRepository, $accountid = null): Response
    {
        $budgetAccounts = $budgetAccountRepository->getUserBudgetAccounts($accountid);

        // Load Stats and inject into entity
        // @TODO come back and turn this into a service and enable it
        // $budgetAccountStatsLoader = new BudgetAccountStatsLoader($this->getDoctrine()->getManager(), $request);
        // $budgetAccountStatsLoader->loadBudgetAccountStats();

        return $this->render(
            'default/budgettransactions.html.twig',
            [
                'budgetaccounts' => $budgetAccounts,
            ]
        );
    }
}
