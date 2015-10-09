<?php

namespace EnvelopeBundle\Controller;

use EnvelopeBundle\Entity\Budget\Template;
use EnvelopeBundle\Entity\BudgetTransaction;
use EnvelopeBundle\Entity\Transaction;
use EnvelopeBundle\Form\Type\TransactionType;
use EnvelopeBundle\Shared\autoCodeTransactions;
use EnvelopeBundle\Shared\importBankTransactions;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;


class DefaultController extends Controller
{
    public function dashboardAction()
    {
        if (!$this->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_FULLY')) {
            return $this->render(
                'EnvelopeBundle:Default:welcome.html.twig'
            );
        }
        return $this->render(
            'EnvelopeBundle:Default:dashboard.html.twig'
        );
    }

    public function profileAction($userid)
    {
        $qb = $this->getDoctrine()->getManager()->createQueryBuilder();
        $qb->select('u')
            ->from('EnvelopeBundle:User', 'u');

        $qb->where('u.username = :username')
            ->setParameter('username', $userid);

        return $this->render(
            'EnvelopeBundle:Default:profile.html.twig',
            [
                'user' => $qb->getQuery()->getResult()[0],
            ]
        );
    }

    public function budgetTransactionListAction($accountid = null)
    {
        $qb = $this->getDoctrine()->getManager()->createQueryBuilder();
        $qb->select('a')
            ->from('EnvelopeBundle:BudgetAccount', 'a');

        if ($accountid) {
            $qb->where('a.id = :id')
                ->setParameter('id', $accountid);

        }

        return $this->render(
            'EnvelopeBundle:Default:budgettransactions.html.twig',
            [
                'budgetaccounts' => $qb->getQuery()->getResult(),
            ]
        );
    }

    private function importForm()
    {
        return $form = $this->createFormBuilder()
            ->add('account', 'entity', ['class' => 'EnvelopeBundle:Account'])
            ->add('accountType', 'choice', ['choices' => ['NAB' => 'NAB', 'ANZ' => 'ANZ']])
            ->add('bankExport', 'file')
            ->add('save', 'submit', array('label' => 'Import transactions'))
            ->getForm();
    }

    public function importAction(Request $request)
    {
        $query = $this->getDoctrine()->getManager()->createQuery(
            'SELECT i FROM EnvelopeBundle:Import i'
        );

        $form = $this->importForm();

        $form->handleRequest($request);

        $dups = [];
        $ignored = [];
        $unknown = null;
        $import = null;

        if ($form->isValid() && $form->isSubmitted()) {
            $bankImport = new importBankTransactions();
            $bankImport->importBankFile(
                $this->getDoctrine()->getManager(),
                $form['bankExport']->getData()->getPathname(),
                $form['account']->getData(),
                $form['accountType']->getData()
            );
            $dups = $bankImport->getDuplicates();
            $ignored = $bankImport->getIgnored();
            $unknown = $bankImport->getUnknown();
            $import = $bankImport->getImport();


        }


        return $this->render(
            'EnvelopeBundle:Default:imports.html.twig',
            [
                'imports' => $query->getResult(),
                'importform' => $form->createView(),
                'lastimport' => $import,
                'lastimportaccount' => $form['account']->getData(),
                'dups' => $dups,
                'ignored' => $ignored,
                'unknown' => $unknown,
            ]
        );
    }

    public function transactionsListAction(Request $request)
    {
        $session = $request->getSession();
        $query = $this->getDoctrine()->getManager()->createQuery(
            'SELECT a
            FROM EnvelopeBundle:Account a
            WHERE a.access_group = :accessgroup
            '
        )->setParameters(['accessgroup' => $session->get('accessgroupid')])
        ;

        $query2 = $this->getDoctrine()->getManager()->createQuery(
            'SELECT t
            FROM EnvelopeBundle:Transaction t
            LEFT JOIN EnvelopeBundle:BudgetTransaction b
            WITH b.transaction = t
            LEFT JOIN EnvelopeBundle:Account a
            WITH t.account = a
            WHERE a.access_group = :accessgroup
            GROUP BY t.id
            HAVING COUNT(b.amount) = 0 OR SUM(b.amount) != t.amount
            ORDER BY t.date
            '
        )->setParameters(['accessgroup' => $session->get('accessgroupid')])
        ;

        return $this->render(
            'EnvelopeBundle:Default:transactions.html.twig',
            [
                'accounts' => $query->getResult(),
                'unbalancedtransactions' => $query2->getResult()
            ]
        );
    }

    public function transactionListAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();
        if ($id == 'new') {
            $existing = false;
            $transaction = new Transaction();
            $transaction->setDate(new \DateTime());
        } else {
            $existing = true;

            $query = $em->createQuery(
                'SELECT t
                    FROM EnvelopeBundle:Transaction t
                    WHERE t.id = :id
                    '
            );

            $query->setParameters(
                [
                    "id" => $id
                ]
            );

            $transaction = $query->getSingleResult();
        }

        $form = $this->createForm(new TransactionType(), $transaction, ['existing_entity' => $existing]);

        $form->handleRequest($request);

        if ($form->isValid()) {

            foreach ($transaction->getBudgetTransactions() as $budgetTransaction) {
                if ($budgetTransaction->getBudgetAccount() == null || $budgetTransaction->getAmount() == null) {
                    $transaction->removeBudgetTransaction($budgetTransaction);
                    $budgetTransaction->setTransaction(null);
                    $em->remove($budgetTransaction);
                }
            }

            if($id == 'new')
            {
                $transaction->setFullDescription($transaction->getDescription());
            }


            $em->persist($transaction);
            $em->flush();

            $this->addFlash(
                'notice',
                'Transaction Updated'
            );

            if($transaction->getId() != $id)
            {
                return $this->redirectToRoute('envelope_transaction', ['id' => $transaction->getId()]);
            }


            $form = $this->createForm(new TransactionType(), $transaction);
        }

        return $this->render(
            'EnvelopeBundle:Default:transaction.html.twig',
            [
                'transaction' => $transaction,
                'addform' => $form->createView(),//$this->transactionAddBudgetTransactionForm($id)->createView()
                'transactionid' => $id,
            ]
        );
    }

    public function autoCodeAction(Request $request)
    {
        $form = $this->createFormBuilder()
            ->add('save', 'submit', array('label' => 'Auto code transactions'))
            ->getForm();

        $form->handleRequest($request);

        $autoCodeResults = [];
        $actionRun = false;

        if ($form->isValid() && $form->isSubmitted()) {
            $autoCode = new autoCodeTransactions();
            $autoCode->codeTransactions($this->getDoctrine()->getManager());
            $autoCodeResults = $autoCode->getResults();
            $actionRun = true;
        }

        return $this->render(
            'EnvelopeBundle:Default:autoCodeAction.html.twig',
            [
                'actionrun' => $actionRun,
                'results' => $autoCodeResults,
                'form' => $form->createView(),
            ]
        );
    }

    public function budgetAccountListAction()
    {
        $query = $this->getDoctrine()->getManager()->createQuery(
            'SELECT b
            FROM EnvelopeBundle:BudgetGroup b
            '
        );

        return $this->render(
            'EnvelopeBundle:Default:budgetaccounts.html.twig',
            array('budgetgroups' => $query->getResult())
        );
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
        foreach ($group_sums_query->getResult() as $part) {
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

        if ($form->isValid()) {

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
            ->setFullDescription("Budget Template Transaction - " . $template->getDescription());
        $em->persist($transferTransaction);

        // Loop through template transactions
        // For each transaction, create a budget transaction linked to bank transaction
        foreach ($template->getTemplateTransactions() as $templateTransaction) {
            $budgetTransaction = new BudgetTransaction();
            $budgetTransaction->setAmount($templateTransaction->getAmount())
                ->setBudgetAccount($templateTransaction->getBudgetAccount())
                ->setTransaction($transferTransaction);
            $em->persist($budgetTransaction);
        }
        $em->flush();

        $this->addFlash(
            'notice',
            'Budget Template Applied'
        );

    }
}
