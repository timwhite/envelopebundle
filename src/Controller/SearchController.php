<?php


namespace Controller;

use Doctrine\DBAL\Types\DecimalType;
use EnvelopeBundle\Entity\BudgetAccount;
use EnvelopeBundle\Shared\BudgetAccountStats;
use EnvelopeBundle\Shared\BudgetAccountStatsLoader;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;


class SearchController extends Controller
{

    public function searchAction(Request $request) {
        $searchTerm = $request->query->get('q');
        $session = $request->getSession();

        $qb = $this->getDoctrine()->getManager()->createQueryBuilder();
        $qb->select('t')
            ->from('EnvelopeBundle:Transaction', 't')
            ->join('EnvelopeBundle:Account', 'a', 'WITH', 't.account = a')
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
            'EnvelopeBundle:Search:results.html.twig',
            [
                'transactions' => $transactions,
                'searchterm' => $searchTerm
            ]
        );
    }
}