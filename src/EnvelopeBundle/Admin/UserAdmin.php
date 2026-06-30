<?php

namespace EnvelopeBundle\Admin;

use Sonata\AdminBundle\Admin\Admin;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Show\ShowMapper;

class UserAdmin extends Admin
{
    protected function configureDatagridFilters(DatagridMapper $datagridMapper)
    {
        $datagridMapper
            ->add('id')
            ->add('firstname')
            ->add('lastname')
            ->add('email')
            ->add('username')
            ->add('avatar')
            ->add('access_group')
        ;
    }

    protected function configureListFields(ListMapper $listMapper)
    {
        $listMapper
            ->add('id')
            ->add('firstname')
            ->add('lastname')
            ->add('email')
            ->add('username')
            ->add('avatar')
            ->add('access_group')
            ->add('_action', 'actions', [
                'actions' => [
                    'show' => [],
                    'edit' => [],
                    'delete' => [],
                ],
            ])
        ;
    }

    protected function configureFormFields(FormMapper $formMapper)
    {
        $formMapper
            ->add('id')
            ->add('firstname')
            ->add('lastname')
            ->add('email')
            ->add('username')
            ->add('avatar')
            ->add('access_group')
        ;
    }

    protected function configureShowFields(ShowMapper $showMapper)
    {
        $showMapper
            ->add('id')
            ->add('firstname')
            ->add('lastname')
            ->add('email')
            ->add('username')
            ->add('avatar')
            ->add('access_group')
        ;
    }
}
