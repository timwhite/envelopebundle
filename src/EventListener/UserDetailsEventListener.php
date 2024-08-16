<?php

namespace App\EventListener;

use KevinPapst\TablerBundle\Event\UserDetailsEvent;
use KevinPapst\TablerBundle\Model\UserInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener(event: UserDetailsEvent::class)]
class UserDetailsEventListener
{
    public function __construct(private Security $security)
    {
    }
    public function __invoke(UserDetailsEvent $event): void
    {
        $user = $this->security->getUser();

        if ($user instanceof UserInterface) {
            $event->setUser($user);
        }

    }
}