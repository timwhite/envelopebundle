<?php

namespace EnvelopeBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use App\Entity\Account;
use App\Entity\BudgetAccount;
use App\Entity\BudgetGroup;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Parser;

class LoadAccountData extends AbstractFixture implements OrderedFixtureInterface {
    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {

        $yaml = new Parser();
        try {
            $BankAccounts = $yaml->parse(file_get_contents('bankaccounts.yaml'));
            $BudgetAccounts = $yaml->parse(file_get_contents('budgetaccounts.yaml'));
        } catch (ParseException $e) {
            printf("Unable to parse the YAML string: %s", $e->getMessage());
            throw $e;
        }

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