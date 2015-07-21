<?php

namespace EnvelopeBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller
{
    public function indexAction($name)
    {
        return $this->render('EnvelopeBundle:Default:index.html.twig', array('name' => $name));
    }

    public function budgetTransactionListAction($accountid = null)
    {
        $qb = $this->getDoctrine()->getManager()->createQueryBuilder();
        $qb->select('a')
           ->from('EnvelopeBundle:BudgetAccount', 'a');

        if($accountid)
        {
            $qb->where('a.id = :id')
               ->setParameter('id', $accountid);

        }

        return $this->render('EnvelopeBundle:Default:budgettransactions.html.twig',
            [
                'budgetaccounts' => $qb->getQuery()->getResult(),
            ]);
    }

    public function transactionListAction()
    {
        $query = $this->getDoctrine()->getManager()->createQuery(
            'SELECT a
            FROM EnvelopeBundle:Account a
            '
        );

        $query2 = $this->getDoctrine()->getManager()->createQuery(
            'SELECT t
            FROM EnvelopeBundle:Transaction t
            LEFT JOIN EnvelopeBundle:BudgetTransaction b
            WHERE b.transaction = t
            GROUP BY t.id
            HAVING COUNT(b.amount) = 0 OR SUM(b.amount) != t.amount
            ORDER BY t.date
            '
        );


        return $this->render('EnvelopeBundle:Default:transactions.html.twig',
            [
                'accounts' => $query->getResult(),
                'unbalancedtransactions' => $query2->getResult()
            ]);
    }

    public function budgetAccountListAction()
    {
        $query = $this->getDoctrine()->getManager()->createQuery(
            'SELECT b
            FROM EnvelopeBundle:BudgetGroup b
            '
        );

        return $this->render('EnvelopeBundle:Default:budgetaccounts.html.twig',
            array('budgetgroups' => $query->getResult()));
    }

    public function budgetTemplateListAction()
    {
        $query = $this->getDoctrine()->getManager()->createQuery(
            'SELECT t
            FROM EnvelopeBundle:Budget\Template t
            '
        );

        return $this->render(
            'EnvelopeBundle:Default:budgettemplates.html.twig',
            array('budgettemplates' => $query->getResult())
        );
    }
}
