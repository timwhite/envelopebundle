<?php

namespace EnvelopeBundle\Auth;


use HWI\Bundle\OAuthBundle\Security\Core\User\OAuthUserProvider;
use HWI\Bundle\OAuthBundle\OAuth\Response\UserResponseInterface;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use EnvelopeBundle\Entity\User;


class OAuthProvider extends OAuthUserProvider
{
    protected $session, $doctrine, $admins;

    public function __construct($session, $doctrine, $service_container)
    {
        $this->session = $session;
        $this->doctrine = $doctrine;
        $this->container = $service_container;
    }

    public function loadUserByUsername($google_id)
    {

        $qb = $this->doctrine->getManager()->createQueryBuilder();
        $qb->select('u')
            ->from('EnvelopeBundle:User', 'u')
            ->where('u.username = :gid')
            ->setParameter('gid', $google_id)
            ->setMaxResults(1);
        $result = $qb->getQuery()->getResult();

        if (count($result)) {
            return $result[0];
        } else {
            throw new UsernameNotFoundException();
        }
    }

    public function loadUserByOAuthUserResponse(UserResponseInterface $response)
    {
        //Data from Google response
        $google_id = $response->getUsername(); /* An ID like: 112259658235204980084 */
        $email = $response->getEmail();
        $nickname = $response->getNickname();
        $realname = $response->getRealName();
        list($firstname, $lastname) = explode(" ", $realname, 2);
        $avatar = $response->getProfilePicture();

        //set data in session
        $this->session->set('email', $email);
        $this->session->set('nickname', $nickname);
        $this->session->set('realname', $realname);
        $this->session->set('avatar', $avatar);

        //Check if this Google user already exists in our app DB
        $qb = $this->doctrine->getManager()->createQueryBuilder();
        $qb->select('u')
            ->from('EnvelopeBundle:User', 'u')
            ->where('u.email = :email')
            ->setParameter('email', $email)
            ->setMaxResults(1);
        $result = $qb->getQuery()->getResult();

        $em = $this->doctrine->getManager();

        //add to database if doesn't exists
        if (!count($result)) {
            $user = new User($google_id);
            $user->setFirstname($firstname);
            $user->setLastname($lastname);
            $user->setEmail($email);
            $user->setAvatar($avatar);

            $em = $this->doctrine->getManager();
            $em->persist($user);
            $em->flush();
        } else {
            $user = $result[0]; /* return User */
            $user->setUsername($google_id);
            $user->setFirstname($firstname);
            $user->setLastname($lastname);
            $user->setEmail($email);
            $user->setAvatar($avatar);
            $em->persist($user);
            $em->flush();
            $this->session->set('id', $user->getId());
        }

        $this->session->set('accessgroupid', $user->getAccessGroup()->getId());

        //set id


        return $user;

    }

    public function supportsClass($class)
    {
        return $class === 'EnvelopeBundle\\Entity\\User';
    }
}