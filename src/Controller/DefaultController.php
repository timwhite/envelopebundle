<?php

namespace App\Controller;

use App\Entity\AccessGroup;
use App\Entity\Account;
use App\Entity\Budget\Template;
use App\Entity\BudgetAccount;
use App\Entity\BudgetTransaction;
use App\Entity\Transaction;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NoResultException;
use EnvelopeBundle\Form\Type\BudgetTemplateType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;

class DefaultController extends AbstractController
{
    public function __construct(private EntityManagerInterface $em)
    {
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
            $enddate = new \DateTime($request->query->get('enddate'));
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

    public function budgetTemplateCloneAction(Request $request, $templateid)
    {
        $session = $request->getSession();
        $budgetTemplateRepo = $this->getDoctrine()->getManager()->getRepository('EnvelopeBundle:Budget\Template');

        /** @var Template $budgetTemplate */
        $budgetTemplate = $budgetTemplateRepo->find($templateid);
        if ($budgetTemplate && $budgetTemplate->getAccessGroup()->getId() == $session->get('accessgroupid')) {
            $newBudgetTemplate = clone $budgetTemplate;
            $this->getDoctrine()->getManager()->persist($newBudgetTemplate);
            $this->getDoctrine()->getManager()->flush();
            $this->addFlash(
                'success',
                'Budget Template '.$budgetTemplate->getName().' cloned'
            );
        } else {
            $this->addFlash(
                'error',
                "Budget Template $templateid doesn't exist to clone"
            );
        }

        return $this->redirectToRoute('envelope_budget_templates');
    }

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
                'accessgroup' => $session->get('accessgroupid'),
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
            'EnvelopeBundle:Default:budgettemplates.html.twig',
            [
                'budgettemplates' => $query->getResult(),
                'budgettemplates_groupsums' => $template_groups,
            ]
        );
    }

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

    public function budgetTemplateDeleteAction(Request $request, $id)
    {
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
                'id' => $id,
                'accessgroup' => $session->get('accessgroupid'),
            ]
        );

        try {
            $budgetTemplate = $query->getSingleResult();
        } catch (NoResultException $e) {
            $this->addFlash('warning', 'No budget template with that ID available to you');

            return $this->redirectToRoute('envelope_budget_templates');
        }
        $this->addFlash('success', 'Budget '.$budgetTemplate->getName().' Deleted');
        $em->remove($budgetTemplate);
        $em->flush();

        return $this->redirectToRoute('envelope_budget_templates');
    }

    public function budgetTemplateEditAction(Request $request, $id)
    {
        $session = $request->getSession();
        $em = $this->getDoctrine()->getManager();
        if ('new' == $id) {
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
                    'id' => $id,
                    'accessgroup' => $session->get('accessgroupid'),
                ]
            );

            try {
                $budgetTemplate = $query->getSingleResult();
            } catch (NoResultException $e) {
                $this->addFlash('warning', 'No budget template with that ID available to you');

                return $this->render(
                    'EnvelopeBundle:Default:dashboard.html.twig');
            }
        }

        $form = $this->createForm(BudgetTemplateType::class, $budgetTemplate, ['existing_entity' => $existing, 'accessgroup' => $session->get('accessgroupid')]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            foreach ($budgetTemplate->getTemplateTransactions() as $templateTransaction) {
                if (
                    null == $templateTransaction->getBudgetAccount()
                    || null == $templateTransaction->getAmount()
                    || null == $templateTransaction->getDescription()
                ) {
                    if ($templateTransaction->getId()) {
                        $em->refresh($templateTransaction);
                        $this->addFlash(
                            'warning',
                            'Removing Template Transaction - '.$templateTransaction
                        );
                    }
                    $budgetTemplate->removeTemplateTransaction($templateTransaction);
                    // $templateTransaction->setTemplate(null);
                    $em->remove($templateTransaction);
                }
                // Ensure that transactions are correctly linked to the template (not sure why this is needed in this case)
                elseif (null == $templateTransaction->getTemplate()) {
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
        if ($form->isSubmitted() && !$form->isValid()) {
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
