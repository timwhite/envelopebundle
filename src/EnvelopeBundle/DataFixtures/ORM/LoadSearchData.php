<?php

namespace EnvelopeBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use EnvelopeBundle\Entity\AutoCodeSearch;

class LoadSearchData implements FixtureInterface {
    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        $budget_searches =
            [
                'Fast Food' => [
                    ' HJ ',
                    'DOMINOS'
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
}