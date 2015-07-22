<?php

namespace EnvelopeBundle\Controller;

use EnvelopeBundle\Entity\Budget\Template;
use EnvelopeBundle\Entity\BudgetTransaction;
use EnvelopeBundle\Entity\Transaction;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;


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

    public function transactionsListAction()
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

    public function transactionListAction($id)
    {
        $query = $this->getDoctrine()->getManager()->createQuery(
            'SELECT t
            FROM EnvelopeBundle:Transaction t
            WHERE t.id = :id
            '
        );

        $query->setParameters([
            "id" => $id
        ]);

        return $this->render('EnvelopeBundle:Default:transaction.html.twig',
            [
                'transaction' => $query->getSingleResult(),
                'addform' => $this->transactionAddBudgetTransactionForm($id)->createView()
            ]);
    }

    private function transactionAddBudgetTransactionForm($transactionid)
    {
        $form = $this->createFormBuilder()
            ->setAction($this->generateUrl('envelope_transactionAddBudgetTransaction', ['id' => $transactionid]))
            ->add('budgetaccount', 'entity', ['class' => 'EnvelopeBundle:BudgetAccount'])
            ->add('amount', 'money')
            ->add('save', 'submit', array('label' => 'Add budget transaction'))
            ->getForm();
        return $form;
    }

    public function transactionAddBudgetTransactionAction(Request $request, $id)
    {
        $form = $this->transactionAddBudgetTransactionForm($id);
        $form->handleRequest($request);

        if ($form->isValid())
        {

/*            $this->applyBudgetTemplate(
                $form->get('template')->getData(),
                $form->get('date')->getData(),
                $form->get('description')->getData()
            );*/

            $this->addFlash(
                'notice',
                'Budget Transaction Added'
            );


        } else {

            $this->addFlash(
                'error',
                'Problem adding budget transaction'
            );
        }

        return $this->redirectToRoute('envelope_transaction', ['id' => $id]);


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

        $group_sums_query = $this->getDoctrine()->getManager()->createQuery(
            'SELECT
              t.id,
              g.name,
              SUM(a.amount) as total
            FROM
              EnvelopeBundle:Budget\Template t
              JOIN t.template_transactions a
              JOIN a.budgetAccount b
              JOIN b.budget_group g
            GROUP BY t.id, b.budget_group
            ORDER by b.budget_group'
        );

        $template_groups = [];
        foreach($group_sums_query->getResult() as $part)
        {
            $template_groups[$part['id']][] = $part;
        }

        return $this->render(
            'EnvelopeBundle:Default:budgettemplates.html.twig',
            [
                'budgettemplates' => $query->getResult(),
                'budgettemplates_groupsums' => $template_groups,
            ]
        );
    }

    public function applyBudgetTemplateAction(Request $request)
    {
        $form = $this->createFormBuilder(['date' => new \DateTime()])
            ->add('template', 'entity', ['class' => 'EnvelopeBundle:Budget\Template'])
            ->add('date', 'date', ['widget' => 'single_text'])
            ->add('description')
            ->add('save', 'submit', array('label' => 'Apply Budget Template'))
            ->getForm();

        $form->handleRequest($request);

        if ($form->isValid())
        {

            $this->applyBudgetTemplate(
                $form->get('template')->getData(),
                $form->get('date')->getData(),
                $form->get('description')->getData()
            );

            return $this->redirectToRoute('envelope_budget_apply_template');
        }

        return $this->render(
            'EnvelopeBundle:Default:applybudgettemplate.html.twig',
            ['form' => $form->createView()]
        );
    }

    private function applyBudgetTemplate(Template $template, $date, $description)
    {
        // Get Special bank account
        $em = $this->getDoctrine()->getManager();
        $budgetTransferAccount = $em
            ->getRepository('EnvelopeBundle:Account')
            ->findOneBy(['name' => 'Budget Transfer']);
        // Create bank transaction for $0
        $transferTransaction = new Transaction();
        $transferTransaction->setDate($date)
            ->setAccount($budgetTransferAccount)
            ->setAmount(0)
            ->setDescription($description)
            ->setFullDescription("Budget Template Transaction - ". $template->getDescription())
        ;
        $em->persist($transferTransaction);

        // Loop through template transactions
        // For each transaction, create a budget transaction linked to bank transaction
        foreach($template->getTemplateTransactions() as $templateTransaction)
        {
            $budgetTransaction = new BudgetTransaction();
            $budgetTransaction->setAmount($templateTransaction->getAmount())
                ->setBudgetAccount($templateTransaction->getBudgetAccount())
                ->setTransaction($transferTransaction)
            ;
            $em->persist($budgetTransaction);
        }
        $em->flush();

        $this->addFlash(
            'notice',
            'Budget Template Applied'
        );

    }
}
