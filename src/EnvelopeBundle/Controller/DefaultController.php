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

        return $this->render('EnvelopeBundle:Default:index.html.twig',
            array('transactions' => $query->getResult()));
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
