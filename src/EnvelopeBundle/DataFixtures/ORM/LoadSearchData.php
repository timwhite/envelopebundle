<?php

namespace EnvelopeBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use EnvelopeBundle\Entity\AutoCodeSearch;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;

class LoadSearchData extends AbstractFixture implements OrderedFixtureInterface {
    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        $budget_searches =
            [
                'Fast Food' => [
                    ' HJ ',
                    'DOMINOS',
                    'MCDONALDS',
                    'KFC'
                ],
                'Tim Work Lunch' => [
                    'CORNER CARVERY AND COFFEE',
                    'AURA BAR'
                ],
                'Petrol' => [
                    'WW PETROL',
                    'FREEDOM FUELS',
                    ' BP '
                ]
            ];

        foreach($budget_searches as $budget_name => $searches){
            $budget_account =  $manager->getRepository('EnvelopeBundle:BudgetAccount')
                ->findOneBy(['budget_name' => $budget_name]);

            foreach($searches as $search_text) {
                $search = new AutoCodeSearch();
                $search->setBudgetAccount($budget_account);
                $search->setSearch($search_text);
                $manager->persist($search);
            }
        }
        $manager->flush();
    }

    /**
     * {@inheritDoc}
     */
    public function getOrder()
    {
        return 2;
    }
}