<?php
namespace EnvelopeBundle\Form\Type;

use Doctrine\ORM\EntityRepository;
use EnvelopeBundle\Entity\BudgetAccount;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class BudgetTransactionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $accessgroup = $options['accessgroup'];
        $builder
            ->add('budgetaccount', EntityType::class, [
                'class' => BudgetAccount::class,
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
            ->add('amount', MoneyType::class, ['required' => false, 'currency' => 'AUD', 'attr' => ['class'   => 'budgetransactionamount']])
        ;

    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults( [
            'data_class' => 'EnvelopeBundle\Entity\BudgetTransaction',
            'accessgroup' => 0
        ] );
    }

    public function getBlockPrefix()
    {
        return 'budgetTransaction';
    }
}