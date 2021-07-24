<?php

namespace EnvelopeBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use App\Entity\Account;
use App\Entity\Budget\Template;
use App\Entity\Budget\TemplateTransaction;
use App\Entity\BudgetAccount;
use App\Entity\BudgetGroup;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Parser;

class LoadBudgetTemplateData extends AbstractFixture implements OrderedFixtureInterface {
    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {

        $yaml = new Parser();
        try {
            $budgetTemplates = $yaml->parse(file_get_contents('budgettemplate.yaml'));
        } catch (ParseException $e) {
            printf("Unable to parse the YAML string: %s", $e->getMessage());
            throw $e;
        }

        /*$budgetTemplates = [
            'Fortnight Income' =>
            [
                'description' => "Fortnightly distribution of income",
                'transactions' => [
                    [
                        'description' => "Tim PM",
                        'amount' => -12,
                        'budget' => "Tim PM"
                    ]
                ]
            ]
        ];*/

        if($budgetTemplates == null)
        {
            $budgetTemplates = [];
        }


        foreach ($budgetTemplates as $name => $template) {
            $Template = new Template();
            $Template->setName($name);
            $Template->setDescription($template['description']);
            $manager->persist($Template);
            foreach ($template['transactions'] as $budget => $transaction)
            {
                if(!isset($transaction['description']))
                {
                    $transaction['description'] = $budget
                    ;
                }
                $budgetAccount = $manager
                    ->getRepository('EnvelopeBundle:BudgetAccount')
                    ->findOneBy(['budget_name' => $budget]);
                $Transaction = new TemplateTransaction();
                $Transaction->setBudgetAccount($budgetAccount)
                    ->setDescription($transaction['description'])
                    ->setAmount($transaction['amount'])
                    ->setTemplate($Template);
                $manager->persist($Transaction);

            }
        }

        $manager->flush();
    }

    /**
     * {@inheritDoc}
     */
    public function getOrder()
    {
        return 3; // the order in which fixtures will be loaded
    }
}