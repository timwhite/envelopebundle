<?php

namespace App\EventListener;

use KevinPapst\AdminLTEBundle\Event\NavbarUserEvent;
use KevinPapst\AdminLTEBundle\Event\ShowUserEvent;
use App\Entity\User;
use KevinPapst\AdminLTEBundle\Event\SidebarUserEvent;
use KevinPapst\AdminLTEBundle\Model\UserModel;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;


class MyShowUseNavBarUserSubscriber implements EventSubscriberInterface
{
    private Security $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }
    public function onShowUser(ShowUserEvent $event)
    {
        if (null === $this->security->getUser()) {
            return;
        }

        /* @var $myUser User */
        $myUser = $this->security->getUser();

        $user = new UserModel();
        $user
            ->setId($myUser->getId())
            ->setName($myUser->getName())
            ->setUsername($myUser->getUsername())
            ->setIsOnline(true)
            //->setTitle('demo user')
            ->setAvatar($myUser->getAvatar())
            //->setMemberSince($myUser->getRegisteredAt())
        ;

        $event->setUser($user);
        $event->setShowLogoutLink(true);
        $event->setShowProfileLink(true);

    }

    public static function getSubscribedEvents(): array
    {
        return [
            NavbarUserEvent::class => ['onShowUser', 100],
            SidebarUserEvent::class => ['onShowUser', 100],
        ];
    }
}