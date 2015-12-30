<?php
namespace EnvelopeBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

class BudgetTemplateTransactionType extends AbstractType
{
    public function onPreSubmitData(FormEvent $event)
    {
        if($event->getData()['budgetaccount'] == '')
        {
            $event->setData(["description" => "", "budgetaccount" => "1", "amount" => "0.00"]);
            return false;
        }
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        $builder
            ->add('budgetaccount', 'entity', ['class' => 'EnvelopeBundle:BudgetAccount', 'required' => false])
            ->add('amount', 'money', ['required' => false, 'currency' => 'AUD', 'attr' => ['class'   => 'budgettemplatetransactionamount']])
            ->add('description', null, ['required' => false])
            ->addEventListener(FormEvents::PRE_SUBMIT, [$this, 'onPreSubmitData'])
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