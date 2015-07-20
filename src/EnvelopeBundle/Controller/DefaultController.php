<?php

namespace EnvelopeBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller
{
    public function indexAction($name)
    {
        return $this->render('EnvelopeBundle:Default:index.html.twig', array('name' => $name));
    }

    public function transactionListAction()
    {
        $query = $this->getDoctrine()->getManager()->createQuery(
            'SELECT t
            FROM EnvelopeBundle:Transaction t
            '
        );

        $query2 = $this->getDoctrine()->getManager()->createQuery(
            'SELECT t
            FROM EnvelopeBundle:Transaction t
            LEFT JOIN EnvelopeBundle:BudgetTransaction b
            WHERE b.transaction = t
            GROUP BY t.id
            HAVING COUNT(b.amount) = 0 OR SUM(b.amount) != t.amount

            '
        );


        return $this->render('EnvelopeBundle:Default:transactions.html.twig',
            [
                'transactions' => $query->getResult(),
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
}
