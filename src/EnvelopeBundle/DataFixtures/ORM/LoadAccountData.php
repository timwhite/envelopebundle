<?php

namespace EnvelopeBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use EnvelopeBundle\Entity\Account;
use EnvelopeBundle\Entity\BudgetAccount;

class LoadAccountData extends AbstractFixture implements OrderedFixtureInterface {
    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        $BankAccounts = ['NAB Cash', 'NAB Everyday', 'ANZ Offset', 'ANZ Credit Card'];
        $BudgetAccounts = [
            'Mortgage',
            'Tim PM',
            'Sara PM',
            'Fast Food',
            'Petrol',
            'Tim Work Lunch'
        ];
        foreach ($BankAccounts as $accountName) {
            $account = new Account();
            $account->setName($accountName);
            $manager->persist($account);
        }
        foreach ($BudgetAccounts as $accountName) {
            $account = new BudgetAccount();
            $account->setBudgetName($accountName);
            $manager->persist($account);
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