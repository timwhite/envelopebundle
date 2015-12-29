<?php
namespace EnvelopeBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class BudgetTemplateTransactionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('budgetaccount', 'entity', ['class' => 'EnvelopeBundle:BudgetAccount', 'required' => false])
            ->add('amount', 'money', ['required' => false, 'currency' => 'AUD', 'attr' => ['class'   => 'budgettemplatetransactionamount']])
            ->add('description', null, ['required' => false])
        ;

    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'EnvelopeBundle\Entity\Budget\TemplateTransaction',
        ));
    }

    public function getName()
    {
        return 'budgetTemplateTransaction';
    }
}