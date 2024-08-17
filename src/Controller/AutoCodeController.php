<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\AutoCodeSearch;
use App\Entity\BudgetAccount;
use App\Entity\BudgetGroup;
use App\Repository\AutoCodeSearchRepository;
use App\Service\autoCodeTransactions;
use App\Voter\AutoCodeSearchVoter;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route(path: '/autocode', name: 'envelope_autocode')]
class AutoCodeController extends AbstractController
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    #[Route(path: '/', name: '')]
    public function autoCode(Request $request, autoCodeTransactions $autoCodeTransactions, AutoCodeSearchRepository $autoCodeSearchRepository)
    {
        $session = $request->getSession();
        $accessGroup = $session->get('accessgroupid');

        $form = $this->createFormBuilder()
            ->add('save', SubmitType::class, ['label' => 'Auto code transactions'])
            ->getForm();

        $form->handleRequest($request);

        $autoCodeResults = [];
        $actionRun = false;

        if ($form->isSubmitted() && $form->isValid()) {
            $autoCodeTransactions->codeTransactions();
            $autoCodeResults = $autoCodeTransactions->getResults();
            $actionRun = true;
        }

        $searches = $autoCodeSearchRepository->findUsersSearches();

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

    #[Route(path: '/edit/new', name: '_new_search')]
    public function autoCodeNew(Request $request): Response
    {
        $search = new AutoCodeSearch();

        return $this->autoCodeSearchEdit($request, $search);
    }

    #[Route(path: '/edit/{id}', name: '_edit_search')]
    #[IsGranted(AutoCodeSearchVoter::EDIT, 'search')]
    public function autoCodeSearchEdit(Request $request, AutoCodeSearch $search): Response
    {
        $user = $this->getUser();
        $form = $this->createFormBuilder($search)
            ->add('budgetAccount', EntityType::class, [
                'class' => BudgetAccount::class,
                'query_builder' => function (EntityRepository $repository) use ($user) {
                    return $repository->createQueryBuilder('budgetAccount')
                        ->leftJoin(BudgetGroup::class, 'budgetGroup', 'WITH', 'budgetAccount.budget_group = budgetGroup')
                        ->where('budgetGroup.access_group = :accessGroup')
                        ->setParameter('accessGroup', $user->getAccessGroup());
                },
            ])
            ->add('search', null, ['label' => 'Search (SQL LIKE %% search string)'])
            ->add('rename')
            ->add('amount', null, ['label' => 'Optional Amount to restrict search to'])
            ->add('save', SubmitType::class, ['label' => 'Save'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($search);
            $this->entityManager->flush();

            $this->addFlash(
                'success',
                'Search Updated'
            );

            return $this->redirectToRoute('envelope_autocode');
        }

        return $this->render(
            'default/autoCodeSearch.html.twig',
            [
                'form' => $form->createView(),
            ]
        );
    }

    #[Route(path: '/delete/{id}', name: '_delete_search', methods: ['POST'])]
    #[IsGranted(AutoCodeSearchVoter::EDIT, 'search')]
    public function autoCodeSearchDelete(Request $request, AutoCodeSearch $search)
    {
        $searchString = $search->getSearch();
        $this->entityManager->remove($search);
        $this->entityManager->flush();

        $this->addFlash(
            'success',
            'Search ('.$searchString.') deleted'
        );

        return $this->redirectToRoute('envelope_autocode');
    }
}
