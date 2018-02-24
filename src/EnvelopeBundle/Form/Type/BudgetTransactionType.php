<?php
namespace EnvelopeBundle\Form\Type;

use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class BudgetTransactionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $accessgroup = $options['accessgroup'];
        $builder
            ->add('budgetaccount', 'entity', [
                'class' => 'EnvelopeBundle:BudgetAccount',
                'required' => false,
                'query_builder' => function(EntityRepository $repository) use($accessgroup) {
                    // EnvelopeBundle:BudgetAccount is the entity we are selecting
                    $qb = $repository->createQueryBuilder('a');
                    return $qb
                        ->join('EnvelopeBundle:BudgetGroup', 'g', 'WITH', 'a.budget_group = g')
                        ->andWhere('g.access_group = :accessgroup')
                        ->setParameter('accessgroup', $accessgroup)
        ;

                    // the function returns a QueryBuilder object
    },
            ])
            ->add('amount', 'money', ['required' => false, 'currency' => 'AUD', 'attr' => ['class'   => 'budgetransactionamount']])
        ;

    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults( [
            'data_class' => 'EnvelopeBundle\Entity\BudgetTransaction',
            'accessgroup' => 0
        ] );
    }

    public function getName()
    {
        return 'budgetTransaction';
    }
}