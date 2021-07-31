<?php

namespace App\Controller;

use App\Entity\AccessGroup;
use App\Entity\Account;
use App\Entity\AutoCodeSearch;
use App\Entity\Budget\Template;
use App\Entity\BudgetAccount;
use App\Entity\BudgetGroup;
use App\Entity\BudgetTransaction;
use App\Entity\Transaction;
use App\Entity\User;
use App\Form\Type\BudgetTemplateType;
use App\Form\Type\TransactionType;
use App\Repository\AutoCodeSearchRepository;
use App\Repository\BudgetTemplateRepository;
use App\Repository\ImportRepository;
use App\Repository\TransactionRepository;
use App\Shared\autoCodeTransactions;
use App\Shared\BudgetAccountStatsLoader;
use App\Shared\importBankTransactions;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Exception;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;


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

    /**
     * @Route(name="profile", path="/profile/{id}/")
     *
     * @param int $id
     *
     * @return Response
     */
    public function profileAction(int $id)
    {
        $user = $this->em->getRepository(User::class)->find($id);

        if ($this->getUser() !== $user) {
            throw new AccessDeniedException();
        }

        return $this->render(
            'default/profile.html.twig',
            [
                'user' => $user,
            ]
        );
    }

    /**
     * @Route(name="envelope_budgettransactions", path="/budgettransactions/{accountId}")
     *
     * @param Request  $request
     * @param int|null $accountId
     *
     * @return mixed
     */
    public function budgetTransactionListAction(Request $request, ?int $accountId = null)
    {
        $session = $request->getSession();

        $qb = $this->em->createQueryBuilder();
        $qb->select('a')
            ->from(BudgetAccount::class, 'a')
            ->join(BudgetGroup::class, 'g', 'WITH', 'a.budget_group = g')
            ->andWhere('g.access_group = :accessgroup')
            ->setParameter('accessgroup', $session->get('accessgroupid'))
        ;

        if ($accountId) {
            $qb->andWhere('a.id = :id')
                ->setParameter('id', $accountId);

        }

        $budgetaccounts = $qb->getQuery()->getResult();

        // Load Stats and inject into entity
        $budgetAccountStatsLoader = new BudgetAccountStatsLoader($this->em, $request);
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
        return $this->createFormBuilder()
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
                $this->em,
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
        $transaction->setDate(new DateTime());
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
        if ($id == 'new') {
            $existing = false;
            $transaction = new Transaction();
            $transaction->setDate(new DateTime());
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
                    $this->em->remove($budgetTransaction);
                }
            }

            if($id == 'new')
            {
                $transaction->setFullDescription($transaction->getDescription());
            }


            $this->em->persist($transaction);
            $this->em->flush();

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

        $form = $this->createFormBuilder()
            ->add('save', SubmitType::class, [ 'label' => 'Auto code transactions' ] )
            ->getForm();

        $form->handleRequest($request);

        $autoCodeResults = [];
        $actionRun = false;

        if ($form->isSubmitted() && $form->isValid()) {
            $autoCode = new autoCodeTransactions();
            $autoCode->codeTransactions($this->em, $accessGroup);
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
     * @param Request    $request
     * @param int|string $id
     *
     * @return RedirectResponse|Response
     */
    public function autoCodeSearchEditAction(Request $request, AutoCodeSearchRepository $autoCodeSearchRepository, $id)
    {
        $session = $request->getSession();
        $accessGroup = $session->get('accessgroupid');

        if ($id == 'new') {
            $search = new AutoCodeSearch();
        } else {
            $search = $autoCodeSearchRepository->find($id);
            if (!$search || $search->getBudgetAccount()->getBudgetGroup()->getAccessGroup()->getId() != $accessGroup) {
                // Attempt to edit a search that assigns to a budget other than ours
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
            $this->em->persist($search);
            $this->em->flush();

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
     * @param Request                  $request
     * @param AutoCodeSearchRepository $autoCodeSearchRepository
     * @param int                      $id
     *
     * @return RedirectResponse
     */
    public function autoCodeSearchDeleteAction(Request $request, AutoCodeSearchRepository $autoCodeSearchRepository, int $id)
    {
        $session = $request->getSession();
        $accessGroup = $session->get('accessgroupid');

        $search = $autoCodeSearchRepository->find($id);
        if (!$search || $search->getBudgetAccount()->getBudgetGroup()->getAccessGroup()->getId() != $accessGroup) {
            // Attempt to delete a search that assigns to a budget other than ours
            $this->addFlash('error', 'No access to a search with that id');
            return $this->redirectToRoute('envelope_autocode');
        }

        $this->em->remove($search);
        $this->em->flush();

        $this->addFlash(
            'success',
            'Search deleted'
        );

        return $this->redirectToRoute('envelope_autocode');
    }

    private function findFirstTransactionDate()
    {
        return $this->em->createQueryBuilder()
            ->select('MIN(t.date)')
            ->from(Transaction::class, 't')
            ->getQuery()
            ->getSingleScalarResult();
    }

    private function findLastTransactionDate()
    {
        return $this->em->createQueryBuilder()
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
     * @throws Exception
     */
    public function budgetAccountListAction(Request $request)
    {
        $session = $request->getSession();

        if ($request->query->get('startdate')) {
            $startdate = new DateTime($request->query->get('startdate'));
        } else {
            $startdate = new DateTime($this->findFirstTransactionDate());
        }
        if ($request->query->get('enddate')) {
            $enddate = new DateTime($request->query->get('enddate'));
        } else {
            $enddate = new DateTime($this->findLastTransactionDate());
        }

        $query = $this->em->getRepository(BudgetGroup::class)->createQueryBuilder('b')
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

    /**
     * @Route(name="envelope_budget_template_clone", path="/budgets/templates/clone/{templateId}")
     *
     * @param Request                  $request
     * @param BudgetTemplateRepository $budgetTemplateRepository
     * @param int                      $templateId
     *
     * @return RedirectResponse
     */
    public function budgetTemplateCloneAction(Request $request, BudgetTemplateRepository $budgetTemplateRepository, int $templateId)
    {
        $budgetTemplate = $budgetTemplateRepository->find($templateId);
        if (!$budgetTemplate) {
            $this->addFlash(
                'error',
                "Budget Template $templateId doesn't exist to clone"
            );
        } else {
            $newBudgetTemplate = clone $budgetTemplate;
            $this->em->persist($newBudgetTemplate);
            $this->em->flush();
            $this->addFlash(
                'success',
                'Budget Template ' . $budgetTemplate->getName() . ' cloned'
            );
        }
        return $this->redirectToRoute('envelope_budget_templates');
    }

    /**
     * @Route(name="envelope_budget_templates", path="/budgets/templates/")
     *
     * @param Session                  $session
     * @param BudgetTemplateRepository $templateRepository
     *
     * @return Response
     */
    public function budgetTemplateListAction(Session $session, BudgetTemplateRepository $templateRepository)
    {

        return $this->render(
            'default/budgettemplates.html.twig',
            [
                'budgettemplates' => $templateRepository->findAllLimitedAccessGroup($session->get('accessgroupid')),
                // TODO: Finish formatting SUMS in a presentable way
                'budgettemplates_groupsums' => $templateRepository->findGroupSums($session->get('accessgroupid')),
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
        $form = $this->createFormBuilder(['date' => new DateTime()])
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
        $session = $request->getSession();
        $budgetTransferAccount = $this->em
            ->getRepository(Account::class)
            ->findOneBy(['access_group' => $session->get('accessgroupid'), 'budgetTransfer' => true]);
        // Create bank transaction for $0
        $transferTransaction = new Transaction();
        $transferTransaction->setDate($date)
            ->setAccount($budgetTransferAccount)
            ->setAmount(0)
            ->setDescription($description)
            ->setFullDescription("Budget Template Transaction - " . $template->getDescription());
        $this->em->persist($transferTransaction);

        // Loop through template transactions
        // For each transaction, create a budget transaction linked to bank transaction
        foreach ($template->getTemplateTransactions() as $templateTransaction) {
            $budgetTransaction = new BudgetTransaction();
            $budgetTransaction->setAmount($templateTransaction->getAmount())
                ->setBudgetAccount($templateTransaction->getBudgetAccount())
                ->setTransaction($transferTransaction);
            $this->em->persist($budgetTransaction);
        }

        // Update last applied date
        $template->setLastAppliedDate($date);
        $this->em->persist($template);
        $this->em->flush();

        $this->addFlash(
            'success',
            'Budget Template Applied'
        );

    }


    /**
     * @Route(name="envelope_budget_template_delete", path="/budgets/template/delete/{template}", methods={"POST"})
     *
     * @param Template $template
     *
     * @return RedirectResponse
     */
    public function budgetTemplateDeleteAction(Template $template)
    {
        $this->denyAccessUnlessGranted('edit', $template);

        $this->addFlash('success', "Budget " . $template->getName() . " Deleted");
        $this->em->remove($template);
        $this->em->flush();
        return $this->redirectToRoute('envelope_budget_templates');
    }

    /**
     * @Route(name="envelope_budget_template_edit", path="/budgets/template/edit/{id}")
     *
     * @param Request $request
     * @param         $id
     *
     * @return RedirectResponse|Response
     */
    public function budgetTemplateEditAction(Request $request, BudgetTemplateRepository $templateRepository, $id)
    {
        $session = $request->getSession();
        if ($id == 'new') {
            $existing = false;
            $budgetTemplate = new Template();

            // Set access group for new templates
            $accessGroup = $this->em->getRepository(AccessGroup::class)->find($session->get('accessgroupid'));
            $budgetTemplate->setAccessGroup($accessGroup);
        } else {
            $existing = true;

            $budgetTemplate = $templateRepository->find($id);
            $this->denyAccessUnlessGranted('edit', $budgetTemplate);
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
                        $this->em->refresh($templateTransaction);
                        $this->addFlash(
                            'warning',
                            'Removing Template Transaction - ' . $templateTransaction
                        );
                    }
                    $budgetTemplate->removeTemplateTransaction($templateTransaction);
                    //$templateTransaction->setTemplate(null);
                    $this->em->remove($templateTransaction);
                }
                // Ensure that transactions are correctly linked to the template (not sure why this is needed in this case)
                elseif ($templateTransaction->getTemplate() == null) {
                    $templateTransaction->setTemplate($budgetTemplate);
                    $this->em->persist($templateTransaction);
                }
            }

            $this->em->persist($budgetTemplate);
            $this->em->flush();

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

    /**
     * @Route(name="envelope_bulk_code", path="/bulkcode", methods={"POST"})
     *
     * @param Request $request
     *
     * @return RedirectResponse
     */
    public function transactionBulkCodeAction(Request $request)
    {
        $bulkTransactions = $request->get('bulktransactions');
        $budgetAccountId = $request->get('transaction')['budget_transactions'][0]['budgetaccount'];
        /** @var BudgetAccount $budgetAccount */
        $budgetAccount = $this->em->getRepository(BudgetAccount::class)->find($budgetAccountId);
        // @TODO ensure all transactions and budgetAccounts are in the correct group
        foreach($bulkTransactions as $transactionId) {
            /** @var Transaction $transaction */
            $transaction = $this->em->getRepository(Transaction::class)->find($transactionId);
            $budgetTransaction = new BudgetTransaction();
            $budgetTransaction->setBudgetAccount($budgetAccount);
            $budgetTransaction->setAmount($transaction->getUnassignedSum());
            $transaction->addBudgetTransaction($budgetTransaction);
            $this->em->persist($budgetTransaction);
            $this->em->persist($transaction);
            $this->addFlash('success', $transaction->getDescription() . ' assigned '. $budgetTransaction->getAmount() . ' to ' . $budgetAccount->getBudgetName());
        }
        $this->em->flush();

        return $this->redirectToRoute('envelope_transactions_unbalanced');

    }
}
