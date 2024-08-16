<?php

namespace EnvelopeBundle\Admin;

use App\Entity\BudgetAccount;
use Sonata\AdminBundle\Admin\Admin;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Form\FormMapper;

class AutoCodeSearchAdmin extends Admin
{
    // Fields to be shown on create/edit forms
    protected function configureFormFields(FormMapper $formMapper)
    {
        $formMapper
            ->add('search', 'text', [ 'label' => 'Search Text' ] )
            ->add('rename', 'text', [ 'label' => 'Transaction Description Rename', 'required' => false ] )
            ->add('budgetAccount', 'entity', [ 'class' => BudgetAccount::class ] )
        ;
    }

    // Fields to be shown on filter forms
    protected function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
        $datagridMapper
            ->add('search')
            ->add('budgetAccount')
        ;
    }

    // Fields to be shown on lists
    protected function configureListFields(ListMapper $listMapper)
    {
        $listMapper
            ->addIdentifier('search')
            ->add('budgetAccount')
            ->add('rename')
        ;
    }
}