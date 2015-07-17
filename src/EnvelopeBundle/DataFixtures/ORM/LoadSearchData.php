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
                'Eat Out' => [
                    [' HJ ', 'HJ'],
                    ['DOMINOS', 'Dominos'],
                    ['MCDONALDS', 'McDonalds'],
                    ['KFC', 'KFC']
                ],
                'Tim Work Lunch' => [
                    ['CORNER CARVERY AND COFFEE', 'Corner Carvery'],
                    ['AURA BAR', 'Aura Bar'],
                    ['VIETNAM HOUSE FORTITUDE', 'Vietnam House'],
                    ['RED LOTUS VIET', 'Red Lotus'],
                    ['GOOD MORNING SUSHI', 'Good Morning Sushi']
                ],
                'Petrol' => [
                    ['WW PETROL', 'Petrol'],
                    ['FREEDOM FUELS', 'Petrol'],
                    [' BP ', 'Petrol'],
                    ['FREEDOM FUELS', 'Petrol'],
                ],
                'Google Play' => [
                    ['GOOGLE *Music', 'Google Play Music']
                ],
                'Bank Account Transfer' => [
                    ['INTERNET TRANSFER Fortnight Cash T S White', 'Fortnight Cash'],
                    ['INTERNET TRANSFER Regular Savings', 'Fortnight Savings'],
                ],
                'Tithe' => [
                    ['INTERNET TRANSFER TITHE', 'Tithe']
                ],
                'Income Distribution' => [
                    ['12366170889420 SCRIPTURE UNION', 'Tim Salary'],
                    ['AUS GOV FAMILIES', 'Family Tax']

                ],
                'Groceries' => [
                    ['ST IVES BAKERY', 'St Ives Bakery']
                ],
                'Prescriptions' => [
                    ['Goodna Day Night ', 'Pharmacy'],

                ]
            ];

        foreach($budget_searches as $budget_name => $searches){
            $budget_account =  $manager->getRepository('EnvelopeBundle:BudgetAccount')
                ->findOneBy(['budget_name' => $budget_name]);

            if($budget_account == null)
            {
                echo "Can't find budget $budget_name";
            }

            foreach($searches as $search_text) {
                $search = new AutoCodeSearch();
                $search->setBudgetAccount($budget_account);
                $search->setSearch($search_text[0]);
                if(sizeof($search_text) > 1) {
                    $search->setRename($search_text[1]);
                }
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