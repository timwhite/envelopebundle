<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\BudgetTemplateRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class BudgetTemplateController extends AbstractController
{
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
}
