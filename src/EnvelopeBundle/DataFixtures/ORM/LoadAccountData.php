<?php

namespace EnvelopeBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use EnvelopeBundle\Entity\Account;
use EnvelopeBundle\Entity\BudgetAccount;
use EnvelopeBundle\Entity\BudgetGroup;

class LoadAccountData extends AbstractFixture implements OrderedFixtureInterface {
    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        $BankAccounts = ['NAB Cash', 'NAB Everyday', 'ANZ Offset', 'ANZ Credit Card', 'Budget Transfer'];
        $BudgetAccounts = [
            'Regular' => [
                'Electricity',
                'Mobile Phone',
                'House Insurance',
                'Transport',
                'Tithe',
                'AOG WR',
                'Stu and Jo',
                'World Vision',
                'Africa',
                'SU',
                'Google Play',
                'Water/Rates',
                'Internet',
                'Kids PM',
                'Car Loan',
                'Lucy Car Loan',
                'Lucy Rent'

            ],
            'Cash' => [
                'Groceries',
                'Kids',
                'Petrol',
                'Family Day',
                'Eat Out',
                'Dates',
                'Tim Work Lunch',
                'God Money',
                'Tim PM',
                'Sara PM',
                'Household Consumables',
                'Clothing',
                'Prescriptions',
                'Medical',
                'Household'



            ],
            'Savings' => [
                'Mortgage',
                'School',
                'Kids Swimming',
                'Dentist',
                'Car Rego',
                'Car Maint',
                'Car Insurance',
                'RACQ',
                'Birthdays Us',
                'Holidays',
                'Christmas Food',
                'Christmas WA',
                'Christmas Photobooks',
                'Christmas Watson',
                'Christmas Us',
                'Birthdays and Parties',
                'Emergency',
                'Missions',
                'Interest Offset',
                'Home Maintenance',
                'Car Savings',
            ],
            'Budget Special' => [
                'Float',
                'Income Distribution',
                'Bank Account Transfer',
                'Car Loan Special',
                'Lucy Car Loan Special'
            ]
        ];
        foreach ($BankAccounts as $accountName) {
            $account = new Account();
            $account->setName($accountName);
            $manager->persist($account);
        }
        foreach ($BudgetAccounts as $BudgetGroup => $BudgetGroupAccounts)
        {
            $budgetGroup = new BudgetGroup();
            $budgetGroup->setName($BudgetGroup);
            $manager->persist($budgetGroup);
            foreach ($BudgetGroupAccounts as $accountName) {
                $account = new BudgetAccount();
                $account->setBudgetName($accountName);
                $account->setBudgetGroup($budgetGroup);
                $manager->persist($account);
            }

        }

        $manager->flush();
    }

    /**
     * {@inheritDoc}
     */
    public function getOrder()
    {
        return 1; // the order in which fixtures will be loaded
    }
}