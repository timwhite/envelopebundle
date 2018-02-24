<?php

namespace EnvelopeBundle\Form\Type;

use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TransactionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('description', null, ['label' => false]);

        $builder->add('budget_transactions', 'collection', [
            'type' => BudgetTransactionType::class,
            'allow_add'    => true,
            'by_reference' => false,
            'allow_delete' => true,
            'label' => false,
            'options' => ['accessgroup' => $options['accessgroup']]

        ] );

        if(!$options['existing_entity']) {

            $builder->add('account', 'entity', [
                "class" => 'EnvelopeBundle\Entity\Account',
                'label' => false,
                'query_builder' => function(EntityRepository $repository) use($options) {
                    // EnvelopeBundle:Account is the entity we are selecting
                    $qb = $repository->createQueryBuilder('a');
                    return $qb
                        ->Where('a.access_group = :accessgroup')
                        ->setParameter('accessgroup', $options['accessgroup']);
                },

                    ]);
            $builder->add('amount', 'money', ['label' => false, 'currency' => 'AUD']);
            $builder->add('date', 'date', ['widget' => 'single_text']);
            //$builder->add('fulldescription', null, ['disabled' => $existing, 'label' => false,]);
        }

        $builder->add('save', 'submit', [ 'label' => 'Update Transaction' ] );
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults( [
            'data_class' => 'EnvelopeBundle\Entity\Transaction',
            'existing_entity' => true,
            'date' => new \DateTime(),
            'accessgroup' => 0,
        ] );
    }

    public function getName()
    {
        return 'transaction';
    }
}