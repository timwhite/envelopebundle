<?php

namespace EnvelopeBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TransactionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('description', null, ['label' => false]);

        $builder->add('budget_transactions', 'collection', array(
            'type' => new BudgetTransactionType(),
            'allow_add'    => true,
            'by_reference' => false,
            'allow_delete' => true,
            'label' => false,

        ));

        if(!$options['existing_entity']) {

            $builder->add('account', 'entity', ["class" => 'EnvelopeBundle\Entity\Account', 'label' => false,]);
            $builder->add('amount', 'money', ['label' => false, 'currency' => 'AUD']);
            $builder->add('date', 'date', ['widget' => 'single_text']);
            //$builder->add('fulldescription', null, ['disabled' => $existing, 'label' => false,]);
        }

        $builder->add('save', 'submit', array('label' => 'Update Transaction'));
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'EnvelopeBundle\Entity\Transaction',
            'existing_entity' => true,
            'date' => new \DateTime(),
        ));
    }

    public function getName()
    {
        return 'transaction';
    }
}