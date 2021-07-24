<?php

namespace App\EventListener;

use Avanzu\AdminThemeBundle\Event\ShowUserEvent;
use App\Entity\User;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;


class MyShowUserListener
{
    protected $session, $doctrine, $admins;

    public function __construct($session, $doctrine, $service_container)
    {
        $this->session = $session;
        $this->doctrine = $doctrine;
        $this->container = $service_container;
    }
    public function onShowUser(ShowUserEvent $event) {

        $user = $this->getUser();
        $event->setUser($user);

    }

    protected function getUser() {
        // retrieve your concrete user model or entity
        $id = $this->session->get('id');


        $qb = $this->doctrine->getManager()->createQueryBuilder();
        $qb->select('u')
            ->from('EnvelopeBundle:User', 'u')
            ->where('u.id = :id')
            ->setParameter('id', $id)
            ->setMaxResults(1);
        $result = $qb->getQuery()->getResult();

        if (count($result)) {
            return $result[0];
        } else {
            throw new UsernameNotFoundException();
        }
    }
}