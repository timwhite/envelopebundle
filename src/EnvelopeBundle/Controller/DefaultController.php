<?php

namespace EnvelopeBundle\Controller;

use Doctrine\ORM\NoResultException;
use EnvelopeBundle\Entity\Budget\Template;
use EnvelopeBundle\Entity\BudgetTransaction;
use EnvelopeBundle\Entity\Transaction;
use EnvelopeBundle\Form\Type\BudgetTemplateType;
use EnvelopeBundle\Form\Type\TransactionType;
use EnvelopeBundle\Shared\autoCodeTransactions;
use EnvelopeBundle\Shared\BudgetAccountStatsLoader;
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

    public function budgetTransactionListAction(Request $request, $accountid = null)
    {
        $session = $request->getSession();

        $qb = $this->getDoctrine()->getManager()->createQueryBuilder();
        $qb->select('a')
            ->from('EnvelopeBundle:BudgetAccount', 'a')
            ->join('EnvelopeBundle:BudgetGroup', 'g', 'WITH', 'a.budget_group = g')
            ->andWhere('g.access_group = :accessgroup')
            ->setParameter('accessgroup', $session->get('accessgroupid'))
        ;

        if ($accountid) {
            $qb->andWhere('a.id = :id')
                ->setParameter('id', $accountid);

        }

        $budgetaccounts = $qb->getQuery()->getResult();

        // Load Stats and inject into entity
        $budgetAccountStatsLoader = new BudgetAccountStatsLoader($this->getDoctrine()->getManager(), $request);
        $budgetAccountStatsLoader->loadBudgetAccountStats();

        return $this->render(
            'EnvelopeBundle:Default:budgettransactions.html.twig',
            [
                'budgetaccounts' => $budgetaccounts,
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
        )->setParameters(['accessgroup' => $session->get('accessgroupid')]);

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
        )->setParameters(['accessgroup' => $session->get('accessgroupid')]);

        return $this->render(
            'EnvelopeBundle:Default:transactions.html.twig',
            [
                'accounts' => $query->getResult(),
                'unbalancedtransactions' => $query2->getResult()
            ]
        );
    }


        public function transactionsListUnBalancedAction(Request $request)
    {
        $session = $request->getSession();

        $query = $this->getDoctrine()->getManager()->createQuery(
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
            'EnvelopeBundle:Default:unbalancedTransactions.html.twig',
            [
                'unbalancedtransactions' => $query->getResult()
            ]
        );
    }

    public function transactionListAction(Request $request, $id)
    {
        $session = $request->getSession();
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
                    JOIN EnvelopeBundle:Account a
                    WITH t.account = a
                    WHERE t.id = :id
                    AND a.access_group = :accessgroup
                    '
            );

            $query->setParameters(
                [
                    "id" => $id,
                    "accessgroup" => $session->get('accessgroupid')
                ]
            );

            try {
                $transaction = $query->getSingleResult();
            } catch(NoResultException $e) {
                $this->addFlash('warning', "No transaction with that ID available to you");
                return $this->render(
                    'EnvelopeBundle:Default:dashboard.html.twig');
            }
        }

        $form = $this->createForm(new TransactionType(), $transaction, [
            'existing_entity' => $existing,
            "accessgroup" => $session->get('accessgroupid')
        ]);

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
                'success',
                'Transaction Updated'
            );

            if($request->query->get('return') == 'transactions' && $transaction->getUnassignedSum() == 0)
            {
                return $this->redirectToRoute('envelope_transactions');
            }

            if($request->query->get('return') == 'unbalanced_transactions' && $transaction->getUnassignedSum() == 0)
            {
                return $this->redirectToRoute('envelope_transactions_unbalanced');
            }

            // Redirecting ensures form is rebuilt completely with refreshed objects
            return $this->redirectToRoute('envelope_transaction', ['id' => $transaction->getId()]);
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
        $session = $request->getSession();

        $form = $this->createFormBuilder()
            ->add('save', 'submit', array('label' => 'Auto code transactions'))
            ->getForm();

        $form->handleRequest($request);

        $autoCodeResults = [];
        $actionRun = false;

        if ($form->isValid() && $form->isSubmitted()) {
            $autoCode = new autoCodeTransactions();
            $autoCode->codeTransactions($this->getDoctrine()->getManager(), $session->get('accessgroupid'));
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

    private function findFirstTransactionDate()
    {
        return $this->getDoctrine()->getManager()->createQueryBuilder()
            ->select('MIN(t.date)')
            ->from('EnvelopeBundle:Transaction', 't')
            ->getQuery()
            ->getSingleScalarResult();
    }

    private function findLastTransactionDate()
    {
        return $this->getDoctrine()->getManager()->createQueryBuilder()
            ->select('MAX(t.date)')
            ->from('EnvelopeBundle:Transaction', 't')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function budgetAccountListAction(Request $request)
    {
        $session = $request->getSession();

        if ($request->query->get('startdate')) {
            $startdate = new \DateTime($request->query->get('startdate'));
        } else {
            $startdate = new \DateTime($this->findFirstTransactionDate());
        }
        if ($request->query->get('enddate')) {
            $enddate = new \DateTime($request->query->get('enddate'));;
        } else {
            $enddate = new \DateTime($this->findLastTransactionDate());
        }

        $query = $this->getDoctrine()->getManager()->createQuery(
            'SELECT b
            FROM EnvelopeBundle:BudgetGroup b
            JOIN EnvelopeBundle:AccessGroup a
            WITH b.access_group = a
            WHERE a.id  = :accessgroup'
        )->setParameters(['accessgroup' => $session->get('accessgroupid')]);
        $budgetgroups = $query->getResult();

        return $this->render(
            'EnvelopeBundle:Default:budgetaccounts.html.twig',
            [
                'budgetgroups' => $budgetgroups,
                'startdate' => $startdate,
                'enddate' => $enddate,
            ]
        );
    }

    public function budgetTemplateCloneAction($templateid)
    {
        $budgetTemplateRepo = $this->getDoctrine()->getManager()->getRepository('EnvelopeBundle:Budget\Template');
        $budgetTemplate = $budgetTemplateRepo->find($templateid);
        if($budgetTemplate) {
            $newBudgetTemplate = clone $budgetTemplate;
            $this->getDoctrine()->getManager()->persist($newBudgetTemplate);
            $this->getDoctrine()->getManager()->flush();
            $this->addFlash(
                'success',
                'Budget Template ' . $budgetTemplate->getName() . ' cloned'
            );
        }else{
            $this->addFlash(
                'error',
                "Budget Template $templateid doesn't exist to clone"
            );
        }
        return $this->redirectToRoute('envelope_budget_templates');
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
                $request,
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

    private function applyBudgetTemplate(Request $request, Template $template, $date, $description)
    {
        // Get Special bank account
        $em = $this->getDoctrine()->getManager();
        $session = $request->getSession();
        $budgetTransferAccount = $em
            ->getRepository('EnvelopeBundle:Account')
            ->findOneBy(['access_group' => $session->get('accessgroupid'), 'budgetTransfer' => true]);
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

        // Update last applied date
        $template->setLastAppliedDate($date);
        $em->persist($template);
        $em->flush();

        $this->addFlash(
            'success',
            'Budget Template Applied'
        );

    }


    public function budgetTemplateDeleteAction($id) {
        $em = $this->getDoctrine()->getManager();
        $query = $em->createQuery(
            'SELECT t
                    FROM EnvelopeBundle:Budget\Template t
                    WHERE t.id = :id
                    '
        );

        $query->setParameters(
            [
                "id" => $id
            ]
        );

        try {
            $budgetTemplate = $query->getSingleResult();
        } catch(NoResultException $e) {
            $this->addFlash('warning', "No budget template with that ID available to you");
            return $this->redirectToRoute('envelope_budget_templates');
        }
        $this->addFlash('success', "Budget " . $budgetTemplate->getName() . " Deleted");
        $em->remove($budgetTemplate);
        $em->flush();
        return $this->redirectToRoute('envelope_budget_templates');

    }

    public function budgetTemplateEditAction(Request $request, $id)
    {
        $session = $request->getSession();
        $em = $this->getDoctrine()->getManager();
        if ($id == 'new') {
            $existing = false;
            $budgetTemplate = new Template();
        } else {
            $existing = true;

            $query = $em->createQuery(
                'SELECT t
                    FROM EnvelopeBundle:Budget\Template t
                    WHERE t.id = :id
                    '
            );

            $query->setParameters(
                [
                    "id" => $id
                ]
            );

            try {
                $budgetTemplate = $query->getSingleResult();
            } catch(NoResultException $e) {
                $this->addFlash('warning', "No budget template with that ID available to you");
                return $this->render(
                    'EnvelopeBundle:Default:dashboard.html.twig');
            }
        }

        $form = $this->createForm(new BudgetTemplateType(), $budgetTemplate, ['existing_entity' => $existing]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            foreach ($budgetTemplate->getTemplateTransactions() as $templateTransaction) {
                if (
                        $templateTransaction->getBudgetAccount() == null
                        || $templateTransaction->getAmount() == null
                        || $templateTransaction->getDescription() == null
                ) {
                    if($templateTransaction->getId())
                    {
                        $em->refresh($templateTransaction);
                        $this->addFlash(
                            'warning',
                            'Removing Template Transaction - ' . $templateTransaction
                        );
                    }
                    $budgetTemplate->removeTemplateTransaction($templateTransaction);
                    //$templateTransaction->setTemplate(null);
                    $em->remove($templateTransaction);
                }
                // Ensure that transactions are correctly linked to the template (not sure why this is needed in this case)
                elseif ($templateTransaction->getTemplate() == null) {
                    $templateTransaction->setTemplate($budgetTemplate);
                    $em->persist($templateTransaction);
                }
            }

/*            if($id == 'new')
            {
                $budgetTemplate->setFullDescription($budgetTemplate->getDescription());
            }*/


            $em->persist($budgetTemplate);
            $em->flush();

            $this->addFlash(
                'success',
                'Budget Template Updated'
            );

            /*
             * Now that we have removed some transactions, we need a complete reload to get the ID's correct in the
             * form, correct solution is to redirect back to this page afresh, also ensures we don't have duplicate POST
             * issues if they try to refresh the page
             */
            return $this->redirectToRoute('envelope_budget_template_edit', ['id' => $budgetTemplate->getId()]);
        }
        if($form->isSubmitted() && ! $form->isValid()) {
            $this->addFlash(
                'error',
                'Changes not saved. Please fix errors'
            );
        }


        return $this->render(
            'EnvelopeBundle:Default:editbudgettemplate.html.twig',
            [
                'template' => $budgetTemplate,
                'addform' => $form->createView(),
                'templateid' => $id,
            ]
        );
    }
}
