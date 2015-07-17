<?php

namespace EnvelopeBundle\Admin;

use Sonata\AdminBundle\Admin\Admin;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Form\FormMapper;

class BudgetTransactionAdmin extends Admin
{
    // Fields to be shown on create/edit forms
    protected function configureFormFields(FormMapper $formMapper)
    {
        $formMapper
            ->add('transaction', 'entity', array('class' => 'EnvelopeBundle\Entity\Transaction'))
            ->add('budgetAccount', 'entity', array('class' => 'EnvelopeBundle\Entity\BudgetAccount'))
            ->add('amount')
        ;
    }

    // Fields to be shown on filter forms
    protected function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
        $datagridMapper
            ->add('transaction')
            ->add('budgetAccount')
        ;
    }

    // Fields to be shown on lists
    protected function configureListFields(ListMapper $listMapper)
    {
        $listMapper
            ->addIdentifier('transaction')
            ->add('budgetAccount')
            ->add('amount')
        ;
    }
}