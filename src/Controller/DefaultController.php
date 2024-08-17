<?php

namespace App\Controller;

use App\Entity\Account;
use App\Entity\Budget\Template;
use App\Entity\BudgetAccount;
use App\Entity\BudgetTransaction;
use App\Entity\Transaction;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class DefaultController extends AbstractController
{
    #[Route(path: '/budgets/templates/apply', name: 'envelope_budget_apply_template')]
    public function applyBudgetTemplateAction(Request $request)
    {
        $session = $request->getSession();
        $form = $this->createFormBuilder(['date' => new \DateTime()])
            ->add('template', EntityType::class, [
                'class' => Template::class,
                'query_builder' => function (EntityRepository $repository) use ($session) {
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
            ->add('fortnightly_automatic', CheckboxType::class, [
                'label' => 'Apply each fortnight from the last applied date until the selected date?',
                'required' => false,
            ])
            ->add('save', SubmitType::class, ['label' => 'Apply Budget Template'])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var Template $template */
            $template = $form->get('template')->getData();
            /** @var \DateTime $date */
            $date = $form->get('date')->getData();

            $description = $form->get('description')->getData();

            if ($form->get('fortnightly_automatic')->getData()) {
                while ($template->getLastAppliedDate() < $date) {
                    $applyDate = clone $template->getLastAppliedDate();
                    $applyDate->modify('+2 weeks');
                    $this->applyBudgetTemplate($request, $template, $applyDate, $description);
                }
            } else {
                $this->applyBudgetTemplate($request, $template, $date, $description);
            }

            return $this->redirectToRoute('envelope_budget_apply_template');
        }

        return $this->render(
            'EnvelopeBundle:Default:applybudgettemplate.html.twig',
            ['form' => $form->createView()]
        );
    }

    private function applyBudgetTemplate(Request $request, Template $template, \DateTime $date, $description)
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
            ->setFullDescription('Budget Template Transaction - '.$template->getDescription());
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
            'Budget Template Applied - '.$date->format('Y-m-d').' - '.$description
        );
    }
}
