<?php

namespace EnvelopeBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use App\Entity\AutoCodeSearch;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Parser;

class LoadSearchData extends AbstractFixture implements OrderedFixtureInterface {
    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        $yaml = new Parser();
        try {
            $budget_searches = $yaml->parse(file_get_contents('autocodesearches.yaml'));
        } catch (ParseException $e) {
            printf("Unable to parse the YAML string: %s", $e->getMessage());
            throw $e;
        }

        if($budget_searches == null)
        {
            $budget_searches = [];
        }


        foreach($budget_searches as $budget_name => $searches){
            $budget_account =  $manager->getRepository('EnvelopeBundle:BudgetAccount')
                ->findOneBy(['budget_name' => $budget_name]);

            if($budget_account == null)
            {
                echo "Can't find budget $budget_name";
                continue;
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