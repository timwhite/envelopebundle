<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Budget\Template;
use App\Form\Type\BudgetTemplateType;
use App\Repository\BudgetTemplateRepository;
use App\Voter\BudgetTemplateVoter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class BudgetTemplateController extends AbstractController
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    #[Route(path: '/budgets/templates/', name: 'envelope_budget_templates')]
    public function budgetTemplateList(BudgetTemplateRepository $budgetTemplateRepository): Response
    {
        $template_groups = [];
        foreach ($budgetTemplateRepository->getBudgetTemplateGroupSums() as $part) {
            $template_groups[$part['id']][] = $part;
        }

        return $this->render(
            'default/budgettemplates.html.twig',
            [
                'budgettemplates' => $budgetTemplateRepository->getUsersBudgetTemplates(),
                'budgettemplates_groupsums' => $template_groups,
            ]
        );
    }

    #[Route(path: '/budgets/templates/clone/{id}', name: 'envelope_budget_template_clone')]
    #[IsGranted(BudgetTemplateVoter::EDIT, 'template')]
    public function budgetTemplateClone(Request $request, Template $template): Response
    {
        $newBudgetTemplate = clone $template;
        $this->entityManager->persist($newBudgetTemplate);
        $this->entityManager->flush();

        $this->addFlash(
            'success',
            'Budget Template '.$template->getName().' cloned'
        );

        return $this->redirectToRoute('envelope_budget_templates');
    }

    #[Route(path: '/budgets/template/delete/{id}', name: 'envelope_budget_template_delete', methods: ['POST'])]
    #[IsGranted(BudgetTemplateVoter::EDIT, 'template')]
    public function budgetTemplateDelete(Request $request, Template $template): Response
    {
        $this->entityManager->remove($template);
        $this->entityManager->flush();
        $this->addFlash('success', 'Budget '.$template->getName().' Deleted');

        return $this->redirectToRoute('envelope_budget_templates');
    }

    #[Route(path: '/budgets/template/new', name: 'envelope_budget_template_new')]
    public function budgetTemplateNew(Request $request): Response
    {
        $budgetTemplate = new Template();
        $budgetTemplate->setAccessGroup($this->getUser()->getAccessGroup());

        return $this->budgetTemplateEditAction($request, $budgetTemplate, false);
    }

    #[Route(path: '/budgets/template/edit/{id}', name: 'envelope_budget_template_edit')]
    #[IsGranted(BudgetTemplateVoter::EDIT, 'template')]
    public function budgetTemplateEditAction(Request $request, Template $template, $existing = true)
    {
        $form = $this->createForm(BudgetTemplateType::class, $template, [
            'existing_entity' => $existing,
            'accessgroup' => $this->getUser()->getAccessGroup(),
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            foreach ($template->getTemplateTransactions() as $templateTransaction) {
                if (
                    null == $templateTransaction->getBudgetAccount()
                    || null == $templateTransaction->getAmount()
                    || null == $templateTransaction->getDescription()
                ) {
                    if ($templateTransaction->getId()) {
                        $this->entityManager->refresh($templateTransaction);
                        $this->addFlash(
                            'warning',
                            'Removing Template Transaction - '.$templateTransaction
                        );
                    }
                    $template->removeTemplateTransaction($templateTransaction);
                    // $templateTransaction->setTemplate(null);
                    $this->entityManager->remove($templateTransaction);
                }
                // Ensure that transactions are correctly linked to the template (not sure why this is needed in this case)
                elseif (null == $templateTransaction->getTemplate()) {
                    $templateTransaction->setTemplate($template);
                    $this->entityManager->persist($templateTransaction);
                }
            }

            $this->entityManager->persist($template);
            $this->entityManager->flush();

            $this->addFlash(
                'success',
                'Budget Template Updated'
            );

            /*
             * Now that we have removed some transactions, we need a complete reload to get the ID's correct in the
             * form, correct solution is to redirect back to this page afresh, also ensures we don't have duplicate POST
             * issues if they try to refresh the page
             */
            return $this->redirectToRoute('envelope_budget_template_edit', ['id' => $template->getId()]);
        }
        if ($form->isSubmitted() && !$form->isValid()) {
            $this->addFlash(
                'error',
                'Changes not saved. Please fix errors'
            );
        }

        return $this->render(
            'default/editbudgettemplate.html.twig',
            [
                'template' => $template,
                'addform' => $form->createView(),
            ]
        );
    }
}
