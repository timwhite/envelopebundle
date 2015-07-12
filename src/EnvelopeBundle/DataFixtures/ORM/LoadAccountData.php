<?php

namespace EnvelopeBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use EnvelopeBundle\Entity\Account;
use EnvelopeBundle\Entity\BudgetAccount;

class LoadAccountData implements FixtureInterface {
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
            'Fast Food'
        ];
        foreach ($BankAccounts as $accountName) {
            $account = new Account();
            $account->setAccountName($accountName);
            $manager->persist($account);
        }
        foreach ($BudgetAccounts as $accountName) {
            $account = new BudgetAccount();
            $account->setBudgetName($accountName);
            $manager->persist($account);
        }

        $manager->flush();
    }
}