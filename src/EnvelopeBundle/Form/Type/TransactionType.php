<?php

namespace EnvelopeBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TransactionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('description');
        $builder->add('budget_transactions', 'collection', array(
            'type' => new BudgetTransactionType(),
            'allow_add'    => true,
            'by_reference' => false,
            'allow_delete' => true,
        ));
        $builder->add('save', 'submit', array('label' => 'Update Transaction'));
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'EnvelopeBundle\Entity\Transaction',
        ));
    }

    public function getName()
    {
        return 'transaction';
    }
}