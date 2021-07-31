<?php

namespace EnvelopeBundle\Admin;

use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;

class TemplateTransactionAdmin extends AbstractAdmin
{
    // Fields to be shown on create/edit forms
    protected function configureFormFields(FormMapper $formMapper)
    {
        $formMapper
            ->add('template', 'entity', [ 'class' => 'App\Entity\Budget\Template'] )
            ->add('budgetAccount', 'entity', [ 'class' => 'App\Entity\BudgetAccount'] )
            ->add('description')
            ->add('amount')
        ;
    }

    // Fields to be shown on filter forms
    protected function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
        $datagridMapper
            ->add('template')
            ->add('budgetAccount')
            ->add('description')
            ->add('amount')
        ;
    }

    // Fields to be shown on lists
    protected function configureListFields(ListMapper $listMapper)
    {
        $listMapper
            ->addIdentifier('description')
            ->add('amount')
            ->add('budgetAccount')
            ->add('template')

        ;
    }
}