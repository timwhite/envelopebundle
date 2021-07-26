<?php

namespace App\Controller;

use App\Entity\BudgetGroup;
use App\Entity\User;
use App\Repository\AutoCodeSearchRepository;
use App\Repository\ImportRepository;
use App\Repository\TransactionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\Query;
use App\Entity\Account;
use App\Entity\AutoCodeSearch;
use App\Entity\Budget\Template;
use App\Entity\BudgetAccount;
use App\Entity\BudgetTransaction;
use App\Entity\Transaction;
use App\Form\Type\BudgetTemplateType;
use App\Form\Type\TransactionType;
use App\Shared\autoCodeTransactions;
use App\Shared\BudgetAccountStatsLoader;
use App\Shared\importBankTransactions;
use App\Entity\AccessGroup;
use Doctrine\ORM\QueryBuilder;
use Http\Discovery\Exception\NotFoundException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;


class DefaultController extends AbstractController
{
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->em = $entityManager;
    }
    /**
     *
     * @Route (name="dashboard", path="/")
     * @param AuthorizationCheckerInterface $authorizationChecker
     *
     * @return mixed
     */
    public function dashboardAction(AuthorizationCheckerInterface $authorizationChecker)
    {
        if (!$authorizationChecker->isGranted('IS_AUTHENTICATED_FULLY')) {
            return $this->render(
                'default/welcome.html.twig'
            );
        }
        return $this->render(
            'default/dashboard.html.twig'
        );
    }

    public function profileAction($userid)
    {
        $qb = $this->getDoctrine()->getManager()->createQueryBuilder();
        $qb->select('u')
            ->from(User::class, 'u');

        $qb->where('u.username = :username')
            ->setParameter('username', $userid);

        return $this->render(
            'default/profile.html.twig',
            [
                'user' => $qb->getQuery()->getResult()[0],
            ]
        );
    }

    /**
     * @Route(name="envelope_budgettransactions", path="/budgettransactions/{accountid}")
     *
     * @param Request $request
     * @param null    $accountid
     *
     * @return mixed
     */
    public function budgetTransactionListAction(Request $request, $accountid = null)
    {
        $session = $request->getSession();

        $qb = $this->getDoctrine()->getManager()->createQueryBuilder();
        $qb->select('a')
            ->from(BudgetAccount::class, 'a')
            ->join(BudgetGroup::class, 'g', 'WITH', 'a.budget_group = g')
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
            'default/budgettransactions.html.twig',
            [
                'budgetaccounts' => $budgetaccounts,
            ]
        );
    }

    private function importForm($accessGroup)
    {
        return $form = $this->createFormBuilder()
            ->add('account', EntityType::class, [
                'class' => Account::class,
                'query_builder' => function(EntityRepository $repository) use ($accessGroup) {
                    // EnvelopeBundle:BudgetAccount is the entity we are selecting
                    $qb = $repository->createQueryBuilder('a');
                    return $qb
                        ->andWhere('a.access_group = :accessgroup')
                        ->setParameter('accessgroup', $accessGroup)
                        ;
                },
            ])
            ->add('accountType', ChoiceType::class, ['choices' => importBankTransactions::$accountTypes])
            ->add('bankExport', FileType::class)
            ->add('save', SubmitType::class, [ 'label' => 'Import transactions' ] )
            ->getForm();
    }

    /**
     * @Route(name="envelope_import", path="/import/")
     *
     * @param Request $request
     *
     * @return mixed
     */
    public function importAction(Request $request, ImportRepository $importRepository)
    {
        $session = $request->getSession();
        $imports = $importRepository->getAllWithCounts();

        $form = $this->importForm($session->get('accessgroupid'));

        $form->handleRequest($request);

        $dups = [];
        $ignored = [];
        $unknown = null;
        $import = null;

        if ($form->isSubmitted() && $form->isValid()) {
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
            'default/imports.html.twig',
            [
                'imports' => $imports,
                'importform' => $form->createView(),
                'lastimport' => $import,
                'lastimportaccount' => $form['account']->getData(),
                'dups' => $dups,
                'ignored' => $ignored,
                'unknown' => $unknown,
            ]
        );
    }

    /**
     * @Route(name="envelope_transactions", path="/transaction/list")
     *
     * @param Request $request
     *
     * @return mixed
     */
    public function transactionsListAction(Request $request)
    {
        $session = $request->getSession();
        $accounts = $this->em->getRepository(Account::class)->findBy(['access_group' => $session->get('accessgroupid')]);

        $query2 = $this->getUnbalancedTransactionsQuery($session->get('accessgroupid'));

        return $this->render(
            'default/transactions.html.twig',
            [
                'accounts' => $accounts,
                'unbalancedtransactions' => $query2->getResult()
            ]
        );
    }

    /**
     * @param $accessgroupid
     *
     * @return Query
     */
    private function getUnbalancedTransactionsQuery($accessgroupid)
    {
        /** @var QueryBuilder $qb */
        $qb = $this->em->getRepository(Transaction::class)->createQueryBuilder('t');
        $qb->leftJoin(BudgetTransaction::class, 'b', Query\Expr\Join::WITH, 'b.transaction = t')
            ->leftJoin(Account::class, 'a', Query\Expr\Join::WITH, 't.account = a')
            ->where('a.access_group = :accessGroup')
            ->groupBy('t.id')
            ->having('(COUNT(b.amount) = 0 AND t.amount != 0) OR SUM(b.amount) != t.amount')
            ->orderBy('t.date')
            ->setParameters(['accessGroup' => $accessgroupid]);
        return $qb->getQuery();
    }


    /**
     * @Route(name="envelope_transactions_unbalanced", path="/transaction/list/unbalanced")
     *
     * @param Request $request
     *
     * @return mixed
     */
    public function transactionsListUnBalancedAction(Request $request)
    {
        $session = $request->getSession();

        $query = $this->getUnbalancedTransactionsQuery($session->get('accessgroupid'));

        // Get form for coding transactions
        $transaction = new Transaction();
        $transaction->setDate(new \DateTime());
        $form = $this->createForm(TransactionType::class, $transaction, [
            'existing_entity' => false,
            "accessgroup" => $session->get('accessgroupid')
        ]);

        return $this->render(
            'default/unbalancedTransactions.html.twig',
            [
                'unbalancedtransactions' => $query->getResult(),
                'codingForm' => $form->createView()
            ]
        );
    }

    /**
     * @Route(name="envelope_transaction", path="/transaction/{id}")
     *
     *
     * Set id to 'new' for creating new transactions
     *
     *
     * @param Request               $request
     * @param                       $id
     * @param TransactionRepository $transactionRepository
     *
     * @return RedirectResponse|Response
     */
    public function transactionListAction(Request $request, $id, TransactionRepository $transactionRepository)
    {
        $session = $request->getSession();
        $em = $this->getDoctrine()->getManager();
        if ($id == 'new') {
            $existing = false;
            $transaction = new Transaction();
            $transaction->setDate(new \DateTime());
        } else {
            $existing = true;

            $transaction = $transactionRepository->find($id);

            // This will deny if the transaction isn't found, or we don't have access to it
            $this->denyAccessUnlessGranted('edit', $transaction);
        }

        $form = $this->createForm(TransactionType::class, $transaction, [
            'existing_entity' => $existing,
            "accessgroup" => $session->get('accessgroupid')
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

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
            'default/transaction.html.twig',
            [
                'transaction' => $transaction,
                'addform' => $form->createView(),//$this->transactionAddBudgetTransactionForm($id)->createView()
                'transactionid' => $id,
            ]
        );
    }

    /**
     * @Route(name="envelope_autocode", path="/autocode/")
     *
     * @param Request $request
     *
     * @return mixed
     */
    public function autoCodeAction(Request $request, AutoCodeSearchRepository $autoCodeSearchRepository)
    {
        $session = $request->getSession();
        $accessGroup = $session->get('accessgroupid');
        $em = $this->getDoctrine()->getManager();

        $form = $this->createFormBuilder()
            ->add('save', SubmitType::class, [ 'label' => 'Auto code transactions' ] )
            ->getForm();

        $form->handleRequest($request);

        $autoCodeResults = [];
        $actionRun = false;

        if ($form->isSubmitted() && $form->isValid()) {
            $autoCode = new autoCodeTransactions();
            $autoCode->codeTransactions($em, $accessGroup);
            $autoCodeResults = $autoCode->getResults();
            $actionRun = true;
        }

        $searches = $autoCodeSearchRepository->findByAccessGroup($accessGroup);

        return $this->render(
            'default/autoCodeAction.html.twig',
            [
                'actionrun' => $actionRun,
                'results' => $autoCodeResults,
                'form' => $form->createView(),
                'searches' => $searches,
            ]
        );
    }

    /**
     * @Route (name="envelope_autocode_edit_search", path="/autocode/edit/{id}")
     *
     * @param Request $request
     * @param         $id
     *
     * @return RedirectResponse|Response
     */
    public function autoCodeSearchEditAction(Request $request, $id)
    {
        $session = $request->getSession();
        $accessGroup = $session->get('accessgroupid');
        $em = $this->getDoctrine()->getManager();

        if ($id == 'new') {
            $search = new AutoCodeSearch();
        } else {
            /** @var AutoCodeSearch $search */
            $search = $em->getRepository(AutoCodeSearch::class)->findOneBy(['id'=>$id]);
            if (!$search || $search->getBudgetAccount()->getBudgetGroup()->getAccessGroup()->getId() != $accessGroup) {
                // Attempt to edit an search that assigns to a budget other than ours
                $this->addFlash('error', 'No access to a search with that id');
                return $this->redirectToRoute('envelope_autocode');
            }
        }

        $form = $this->createFormBuilder($search)
            ->add('budgetAccount', EntityType::class, [
                'class' => BudgetAccount::class,
                'query_builder' => function(EntityRepository $repository) use($accessGroup) {
                    $qb = $repository->createQueryBuilder('b');
                    return $qb
                        ->join(BudgetGroup::class, 'g', 'WITH', 'b.budget_group = g')
                        ->Where('g.access_group = :accessgroup')
                        ->setParameter('accessgroup', $accessGroup);
                },
            ])
            ->add('search',null,['label' => "Search (SQL LIKE %% search string)"])
            ->add('rename')
            ->add('amount', null, ['label' => "Optional Amount to restrict search to"])
            ->add('save', SubmitType::class, [ 'label' => 'Save' ] )
            ->getForm();

        $form->handleRequest($request);


        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($search);
            $em->flush();

            $this->addFlash(
                'success',
                'Search Updated'
            );

            return $this->redirectToRoute('envelope_autocode');

        }

        return $this->render(
            'default/autoCodeSearch.html.twig',
            [
                'form' => $form->createView()
            ]
        );
    }

    /**
     * @Route (name="envelope_autocode_delete_search", path="/autocode/delete/{id}", methods={"POST"})
     *
     * @param Request $request
     * @param         $id
     *
     * @return RedirectResponse
     */
    public function autoCodeSearchDeleteAction(Request $request, $id)
    {
        $session = $request->getSession();
        $accessGroup = $session->get('accessgroupid');
        $em = $this->getDoctrine()->getManager();

        /** @var AutoCodeSearch $search */
        $search = $em->getRepository(AutoCodeSearch::class)->findOneBy(['id'=>$id]);
        if (!$search || $search->getBudgetAccount()->getBudgetGroup()->getAccessGroup()->getId() != $accessGroup) {
            // Attempt to delete an search that assigns to a budget other than ours
            $this->addFlash('error', 'No access to a search with that id');
            return $this->redirectToRoute('envelope_autocode');
        }

        $em->remove($search);
        $em->flush();

        $this->addFlash(
            'success',
            'Search deleted'
        );

        return $this->redirectToRoute('envelope_autocode');
    }

    private function findFirstTransactionDate()
    {
        return $this->getDoctrine()->getManager()->createQueryBuilder()
            ->select('MIN(t.date)')
            ->from(Transaction::class, 't')
            ->getQuery()
            ->getSingleScalarResult();
    }

    private function findLastTransactionDate()
    {
        return $this->getDoctrine()->getManager()->createQueryBuilder()
            ->select('MAX(t.date)')
            ->from(Transaction::class, 't')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @Route(name="envelope_budgets", path="/budgetaccounts/")
     *
     * @param Request $request
     *
     * @return mixed
     * @throws \Exception
     */
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

        $query = $this->getDoctrine()->getRepository(BudgetGroup::class)->createQueryBuilder('b')
            ->leftJoin(AccessGroup::class, 'a', Query\Expr\Join::WITH, 'b.access_group = a')
            ->where('a.id = :accessGroup')
            ->setParameters(['accessGroup' => $session->get('accessgroupid')])
            ->getQuery();
        $budgetgroups = $query->getResult();

        return $this->render(
            'default/budgetaccounts.html.twig',
            [
                'budgetgroups' => $budgetgroups,
                'startdate' => $startdate,
                'enddate' => $enddate,
            ]
        );
    }

    public function budgetTemplateCloneAction(Request $request, $templateid)
    {
        $session = $request->getSession();
        $budgetTemplateRepo = $this->getDoctrine()->getManager()->getRepository(Template::class);

        /** @var Template $budgetTemplate */
        $budgetTemplate = $budgetTemplateRepo->find($templateid);
        if($budgetTemplate && $budgetTemplate->getAccessGroup()->getId() == $session->get('accessgroupid')) {
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

    /**
     * @Route(name="envelope_budget_templates", path="/budgets/templates/")
     *
     * @param Request $request
     *
     * @return mixed
     */
    public function budgetTemplateListAction(Request $request)
    {
        $session = $request->getSession();
        $query = $this->getDoctrine()->getManager()->createQuery(
            'SELECT t
            FROM EnvelopeBundle:Budget\Template t
            WHERE t.access_group = :accessgroup
            '
        );
        $query->setParameters(
            [
                "accessgroup" => $session->get('accessgroupid')
            ]
        );

        // TODO: Finish formatting SUMS in a presentable way
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
            'default/budgettemplates.html.twig',
            [
                'budgettemplates' => $query->getResult(),
                'budgettemplates_groupsums' => $template_groups,
            ]
        );
    }

    /**
     * @Route(name="envelope_budget_apply_template", path="/budget/templates/apply")
     *
     * @param Request $request
     *
     * @return mixed
     */
    public function applyBudgetTemplateAction(Request $request)
    {
        $session = $request->getSession();
        $form = $this->createFormBuilder(['date' => new \DateTime()])
            ->add('template', EntityType::class, [
                'class' => Template::class,
                'query_builder' => function(EntityRepository $repository) use ($session) {
                    // EnvelopeBundle:BudgetAccount is the entity we are selecting
                    $qb = $repository->createQueryBuilder('t');
                    return $qb
                        ->andWhere('t.archived = 0')
                        ->andWhere('t.access_group = :accessgroup')
                        ->setParameter('accessgroup', $session->get('accessgroupid'))
                        ;
                },
                ])
            ->add('date', DateType::class, ['widget' => 'single_text'])
            ->add('description')
            ->add('save', SubmitType::class, [ 'label' => 'Apply Budget Template' ] )
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $this->applyBudgetTemplate(
                $request,
                $form->get('template')->getData(),
                $form->get('date')->getData(),
                $form->get('description')->getData()
            );

            return $this->redirectToRoute('envelope_budget_apply_template');
        }

        return $this->render(
            'default/applybudgettemplate.html.twig',
            ['form' => $form->createView()]
        );
    }

    private function applyBudgetTemplate(Request $request, Template $template, $date, $description)
    {
        // Get Special bank account
        $em = $this->getDoctrine()->getManager();
        $session = $request->getSession();
        $budgetTransferAccount = $em
            ->getRepository(Account::class)
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


    public function budgetTemplateDeleteAction(Request $request, $id) {
        $session = $request->getSession();
        $em = $this->getDoctrine()->getManager();
        $query = $em->createQuery(
            'SELECT t
                    FROM EnvelopeBundle:Budget\Template t
                    WHERE t.id = :id
                    AND t.access_group = :accessgroup
                    '
        );

        $query->setParameters(
            [
                "id" => $id,
                "accessgroup" => $session->get('accessgroupid')
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

            // Set access group for new templates
            $accessGroup = $em->getRepository(AccessGroup::class)->find($session->get('accessgroupid'));
            $budgetTemplate->setAccessGroup($accessGroup);
        } else {
            $existing = true;

            $query = $em->createQuery(
                'SELECT t
                    FROM EnvelopeBundle:Budget\Template t
                    WHERE t.id = :id
                    AND t.access_group = :accessgroup
                    '
            );

            $query->setParameters(
                [
                    "id" => $id,
                    "accessgroup" => $session->get('accessgroupid')
                ]
            );

            try {
                $budgetTemplate = $query->getSingleResult();
            } catch(NoResultException $e) {
                $this->addFlash('warning', "No budget template with that ID available to you");
                return $this->render(
                    'default/dashboard.html.twig');
            }
        }

        $form = $this->createForm(BudgetTemplateType::class, $budgetTemplate, ['existing_entity' => $existing, 'accessgroup' => $session->get('accessgroupid')]);

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
            'default/editbudgettemplate.html.twig',
            [
                'template' => $budgetTemplate,
                'addform' => $form->createView(),
                'templateid' => $id,
            ]
        );
    }

    public function transactionBulkCodeAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        $bulkTransactions = $request->get('bulktransactions');
        $budgetAccountId = $request->get('transaction')['budget_transactions'][0]['budgetaccount'];
        /** @var BudgetAccount $budgetAccount */
        $budgetAccount = $em->getRepository(BudgetAccount::class)->find($budgetAccountId);
        // @TODO ensure all transactions and budgetAccounts are in the correct group
        foreach($bulkTransactions as $transactionId) {
            /** @var Transaction $transaction */
            $transaction = $em->getRepository(Transaction::class)->find($transactionId);
            $budgetTransaction = new BudgetTransaction();
            $budgetTransaction->setBudgetAccount($budgetAccount);
            $budgetTransaction->setAmount($transaction->getUnassignedSum());
            $transaction->addBudgetTransaction($budgetTransaction);
            $em->persist($budgetTransaction);
            $em->persist($transaction);
            $this->addFlash('success', $transaction->getDescription() . ' assigned '. $budgetTransaction->getAmount() . ' to ' . $budgetAccount->getBudgetName());
        }
        $em->flush();

        return $this->redirectToRoute('envelope_transactions_unbalanced');

    }
}
