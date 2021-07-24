<?php

namespace EnvelopeBundle\Admin;

use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;

class TransactionAdmin extends AbstractAdmin
{
    // Fields to be shown on create/edit forms
    protected function configureFormFields(FormMapper $formMapper)
    {
        $formMapper
            ->add('description')
            ->add('date')
            ->add('fullDescription')
            ->add('account', 'entity', [ 'class' => 'App\Entity\Account'] )
            ->add('amount')
        ;
    }

    // Fields to be shown on filter forms
    protected function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
        $datagridMapper
            ->add('description')
            ->add('date')
            ->add('account')
        ;
    }

    // Fields to be shown on lists
    protected function configureListFields(ListMapper $listMapper)
    {
        $listMapper
            ->add('date')
            ->addIdentifier('description')
            ->add('fullDescription')
            ->add('account')
            ->add('amount')
            ->add('unassignedSum')
        ;
    }
}