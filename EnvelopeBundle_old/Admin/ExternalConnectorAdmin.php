<?php

declare(strict_types=1);

namespace EnvelopeBundle\Admin;

use Sonata\AdminBundle\Admin\AbstractAdmin;
use Sonata\AdminBundle\Datagrid\DatagridMapper;
use Sonata\AdminBundle\Datagrid\ListMapper;
use Sonata\AdminBundle\Form\FormMapper;
use Sonata\AdminBundle\Show\ShowMapper;

final class ExternalConnectorAdmin extends AbstractAdmin
{
    protected function configureDatagridFilters(DatagridMapper $datagridMapper): void
    {
        $datagridMapper
            ->add('systemId')
            ->add('systemType')
            ->add('systemCredential')
            ->add('account')
        ;
    }

    protected function configureListFields(ListMapper $listMapper): void
    {
        $listMapper
            ->add('systemId')
            ->add('systemType')
            ->add('systemCredential')
            ->add('account')
            ->add('_action', null, [
                'actions' => [
                    'show' => [],
                    'edit' => [],
                    'delete' => [],
                ],
            ])
        ;
    }

    protected function configureFormFields(FormMapper $formMapper): void
    {
        $formMapper
            ->add('systemId')
            ->add('systemType')
            ->add('systemCredential')
            ->add('account', 'entity', [ 'class' => 'App\Entity\Account'] )
        ;
    }

    protected function configureShowFields(ShowMapper $showMapper): void
    {
        $showMapper
            ->add('systemId')
            ->add('systemType')
            ->add('systemCredential')
            ->add('account')
        ;
    }
}
