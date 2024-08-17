<?php

namespace App\Controller;

use App\Entity\Account;
use App\Repository\ImportRepository;
use App\Service\importBankTransactions;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ImportController extends AbstractController
{
    private function importForm($accessGroup): FormInterface
    {
        return $form = $this->createFormBuilder()
            ->add('account', EntityType::class, [
                'class' => Account::class,
                'query_builder' => function (EntityRepository $repository) use ($accessGroup) {
                    // EnvelopeBundle:BudgetAccount is the entity we are selecting
                    $qb = $repository->createQueryBuilder('a');

                    return $qb
                        ->andWhere('a.access_group = :accessgroup')
                        ->setParameter('accessgroup', $accessGroup)
                    ;
                },
            ])
            ->add('accountType', ChoiceType::class, ['choices' => importBankTransactions::ACCOUNT_TYPES])
            ->add('bankExport', FileType::class)
            ->add('save', SubmitType::class, ['label' => 'Import transactions'])
            ->getForm();
    }

    #[Route(path: '/import/', name: 'envelope_import')]
    public function import(Request $request, ImportRepository $importRepository, importBankTransactions $bankImport): Response
    {
        $form = $this->importForm($this->getUser()->getAccessGroup());

        $form->handleRequest($request);

        $dups = [];
        $ignored = [];
        $unknown = null;
        $import = null;

        if ($form->isSubmitted() && $form->isValid()) {
            $bankImport->importBankFile(
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
                'imports' => $importRepository->findAll(),
                'importform' => $form->createView(),
                'lastimport' => $import,
                'lastimportaccount' => $form['account']->getData(),
                'dups' => $dups,
                'ignored' => $ignored,
                'unknown' => $unknown,
            ]
        );
    }
}
