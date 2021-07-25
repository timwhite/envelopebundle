<?php


namespace App\Controller;

use App\Entity\Account;
use App\Entity\Transaction;
use Doctrine\DBAL\Types\DecimalType;
use App\Entity\BudgetAccount;
use App\Shared\BudgetAccountStats;
use App\Shared\BudgetAccountStatsLoader;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;


class SearchController extends AbstractController
{

    public function searchAction(Request $request) {
        $searchTerm = $request->query->get('q');
        $session = $request->getSession();

        $qb = $this->getDoctrine()->getManager()->createQueryBuilder();
        $qb->select('t')
            ->from(Transaction::class, 't')
            ->join(Account::class, 'a', 'WITH', 't.account = a')
            ->andWhere('a.access_group = :accessgroup')
            ->andWhere('t.fullDescription LIKE :search OR t.description LIKE :search')
            ->setParameters([
                'accessgroup' => $session->get('accessgroupid'),
                    'search' => "%$searchTerm%"
                ]
            )
        ;
        $transactions = $qb->getQuery()->getResult();


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