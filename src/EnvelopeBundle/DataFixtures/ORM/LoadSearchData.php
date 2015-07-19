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
                    ['GOOD MORNING SUSHI', 'Good Morning Sushi'],
                    ['SUSHI ON THE RUN', 'Sushi on the run'],
                ],
                'Petrol' => [
                    ['WW PETROL', 'Petrol'],
                    ['FREEDOM FUELS', 'Petrol'],
                    [' BP ', 'Petrol'],
                    ['FREEDOM FUELS', 'Petrol'],
                    ['FIFTY FIVE TRADING', 'Petrol'],
                    ['CALTEX', 'Petrol'],
                ],
                'Electricity' => [
                    ['Click Energy', 'Electricity']
                ],
                'Internet' => [
                    ['IINET LIMITED PERTH', 'Internet Iinet']
                ],
                'Car Loan' => [
                    ['INTERNET TRANSFER Car Repay', 'Car Repayment']
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
                'World Vision' => [
                    ['WORLD VISION AUSTRALIA', 'World Vision']
                ],
                'Stu and Jo' => [
                    ['INTERNET TRANSFER SUPPORT', 'Stu & Jo Support']
                ],
                'Africa' => [
                    ['OPERATION AFRICA', 'Operation Africa']
                ],
                'AOG WR' => [
                    ['Winds ACC Inter', 'Kelvin AOG WR']
                ],
                'SU' => [
                    ['SU QLD FORTITUDE', 'SU Support']
                ],

                'Mobile Phone' => [
                    ['INTERNET TRANSFER Sara Phone', 'Sara Mobile']
                ],
                'Transport' => [
                    ['TRANSLINK TRANSIT AU', 'Go Card'],
                    ['QLD MOTORWAYS MANAGEMENT EIGHT MILE', 'Go Via']
                ],

                'Income Distribution' => [
                    ['12366170889420 SCRIPTURE UNION', 'Tim Salary'],
                    ['AUS GOV FAMILIES', 'Family Tax']

                ],
                'Lucy Rent' => [
                    ['Rent Lucy Watson', 'Lucy Rent']
                ],
                'Groceries' => [
                    ['ST IVES BAKERY', 'St Ives Bakery'],
                    ['FOODWORKS VALLEY', 'Foodworks'],
                ],
                'Prescriptions' => [
                    ['Goodna Day Night ', 'Pharmacy'],
                    ['TERRY WHITE CHEMISTS', 'Pharmacy']

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