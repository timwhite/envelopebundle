<?php

namespace App\Controller;

use App\Entity\BudgetTransaction;
use App\Entity\Transaction;
use App\Form\Type\TransactionType;
use App\Repository\AccountRepository;
use App\Repository\BudgetAccountRepository;
use App\Repository\TransactionRepository;
use App\Voter\BudgetAccountVoter;
use App\Voter\TransactionVoter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class TransactionController extends AbstractController
{
    public function __construct(private EntityManagerInterface $em)
    {
    }

    #[Route(path: '/transaction/list', name: 'envelope_transactions')]
    public function transactionsList(TransactionRepository $transactionRepository, AccountRepository $accountRepository): Response
    {
        return $this->render(
            'default/transactions.html.twig',
            [
                'accounts' => $accountRepository->getUsersAccounts(),
                // 'unbalancedtransactions' => $transactionRepository->getUnbalancedTransactions()
            ]
        );
    }

    #[Route(path: '/transaction/new', name: 'envelope_transaction_new')]
    public function transactionNew(Request $request): Response
    {
        $transaction = new Transaction();
        $transaction->setDate(new \DateTime());

        return $this->transactionList($transaction, $request, false);
    }

    #[Route(path: '/transaction/{id}', name: 'envelope_transaction')]
    #[IsGranted(TransactionVoter::EDIT, 'transaction')]
    public function transactionList(Transaction $transaction, Request $request, $existing = true): Response
    {
        $form = $this->createForm(TransactionType::class, $transaction, [
            'existing_entity' => $existing,
            'accessgroup' => $this->getUser()->getAccessGroup(),
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            foreach ($transaction->getBudgetTransactions() as $budgetTransaction) {
                if (null == $budgetTransaction->getBudgetAccount() || null == $budgetTransaction->getAmount()) {
                    $transaction->removeBudgetTransaction($budgetTransaction);
                    $budgetTransaction->setTransaction(null);
                    $this->em->remove($budgetTransaction);
                }
            }

            if (!$existing) {
                $transaction->setFullDescription($transaction->getDescription());
            }

            $this->em->persist($transaction);
            $this->em->flush();

            $this->addFlash(
                'success',
                'Transaction Updated'
            );

            if ('transactions' == $request->query->get('return') && 0 == $transaction->getUnassignedSum()) {
                return $this->redirectToRoute('envelope_transactions');
            }

            if ('unbalanced_transactions' == $request->query->get('return') && 0 == $transaction->getUnassignedSum()) {
                return $this->redirectToRoute('envelope_transactions_unbalanced');
            }

            // Redirecting ensures form is rebuilt completely with refreshed objects
            return $this->redirectToRoute('envelope_transaction', ['id' => $transaction->getId()]);
        }

        return $this->render(
            'default/transaction.html.twig',
            [
                'transaction' => $transaction,
                'addform' => $form->createView(), // $this->transactionAddBudgetTransactionForm($id)->createView()
            ]
        );
    }

    #[Route(path: '/transaction/list/unbalanced', name: 'envelope_transactions_unbalanced')]
    public function transactionsListUnBalancedAction(Request $request, TransactionRepository $transactionRepository): Response
    {
        // Get form for coding transactions
        $transaction = new Transaction();
        $transaction->setDate(new \DateTime());
        $form = $this->createForm(TransactionType::class, $transaction, [
            'existing_entity' => false,
            'accessgroup' => $this->getUser()->getAccessGroup(),
        ]);

        return $this->render(
            'default/unbalancedTransactions.html.twig',
            [
                'unbalancedtransactions' => $transactionRepository->getUnbalancedTransactions(),
                'codingForm' => $form->createView(),
            ]
        );
    }

    #[Route(path: '/transaction/{id}/code_api', name: 'envelope_transaction_code_api', options: ['expose' => true])]
    #[IsGranted(TransactionVoter::EDIT, 'transaction')]
    public function transactionCodeApi(Transaction $transaction, Request $request, TransactionRepository $transactionRepository, BudgetAccountRepository $budgetAccountRepository): JsonResponse
    {
        $budgetAccountId = $request->query->get('budgetAccountId');
        $budgetAccount = $budgetAccountRepository->find($budgetAccountId);
        $this->denyAccessUnlessGranted(BudgetAccountVoter::EDIT, $budgetAccount);

        $budgetTransaction = new BudgetTransaction();
        $budgetTransaction->setBudgetAccount($budgetAccount);
        $budgetTransaction->setAmount($transaction->getUnassignedSum());
        $transaction->addBudgetTransaction($budgetTransaction);

        $transactionRepository->persistTransaction($transaction);

        return new JsonResponse(['success' => true, 'transactionId' => $transaction->getId()]);
    }

    #[Route(path: '/bulkcode', name: 'envelope_bulk_code', methods: ['POST'])]
    public function transactionBulkCodeAction(Request $request, TransactionRepository $transactionRepository, BudgetAccountRepository $budgetAccountRepository): Response
    {
        $bulkTransactions = $request->get('bulktransactions');
        $budgetAccountId = $request->get('transaction')['budget_transactions'][0]['budgetaccount'];

        $budgetAccount = $budgetAccountRepository->find($budgetAccountId);
        $this->denyAccessUnlessGranted(BudgetAccountVoter::EDIT, $budgetAccount);

        foreach ($bulkTransactions as $transactionId) {
            /** @var Transaction $transaction */
            $transaction = $transactionRepository->find($transactionId);
            $this->denyAccessUnlessGranted(TransactionVoter::EDIT, $transaction);

            $budgetTransaction = new BudgetTransaction();
            $budgetTransaction->setBudgetAccount($budgetAccount);
            $budgetTransaction->setAmount($transaction->getUnassignedSum());
            $transaction->addBudgetTransaction($budgetTransaction);

            $transactionRepository->persistTransaction($transaction);

            $this->addFlash('success', $transaction->getDescription().' assigned '.$budgetTransaction->getAmount().' to '.$budgetAccount->getBudgetName());
        }

        return $this->redirectToRoute('envelope_transactions_unbalanced');
    }
}
