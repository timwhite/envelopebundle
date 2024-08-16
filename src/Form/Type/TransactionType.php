<?php

namespace App\Form\Type;

use App\Entity\Transaction;
use Doctrine\ORM\EntityRepository;
use App\Entity\Account;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TransactionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('description', null, ['label' => false]);

        $builder->add('budget_transactions', CollectionType::class, [
            'entry_type' => BudgetTransactionType::class,
            'allow_add'    => true,
            'by_reference' => false,
            'allow_delete' => true,
            'label' => false,
            'entry_options' => ['accessgroup' => $options['accessgroup']]

        ] );

        if(!$options['existing_entity']) {

            $builder->add('account', EntityType::class, [
                'class' => Account::class,
                'label' => false,
                'query_builder' => function(EntityRepository $repository) use($options) {
                    // EnvelopeBundle:Account is the entity we are selecting
                    $qb = $repository->createQueryBuilder('a');
                    return $qb
                        ->Where('a.access_group = :accessgroup')
                        ->setParameter('accessgroup', $options['accessgroup']);
                },

                    ]);
            $builder->add('amount', MoneyType::class, ['label' => false, 'currency' => 'AUD']);
            $builder->add('date', DateType::class, ['widget' => 'single_text']);
            //$builder->add('fulldescription', null, ['disabled' => $existing, 'label' => false,]);
        }

        $builder->add('save', SubmitType::class, [ 'label' => 'Update Transaction' ] );
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults( [
            'data_class' => Transaction::class,
            'existing_entity' => true,
            'date' => new \DateTime(),
            'accessgroup' => 0,
        ] );
    }

    public function getBlockPrefix()
    {
        return 'transaction';
    }
}