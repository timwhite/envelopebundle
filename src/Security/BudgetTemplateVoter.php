<?php


namespace App\Security;


use App\Entity\Budget\Template;
use App\Entity\User;
use LogicException;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class BudgetTemplateVoter extends Voter
{
    const EDIT = 'edit';
    const ATTRIBUTES = [self::EDIT];

    protected function supports($attribute, $subject)
    {
        if (!in_array($attribute, self::ATTRIBUTES)) {
            return false;
        }

        if (!$subject instanceof Template) {
            return false;
        }

        return true;
    }

    /**
     * @param string         $attribute
     * @param mixed          $subject
     * @param TokenInterface $token
     *
     * @return bool
     */
    protected function voteOnAttribute($attribute, $subject, TokenInterface $token)
    {
        /** @var User $user */
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        /** @var Template $template */
        $template = $subject;

        switch ($attribute) {
            case self::EDIT:
                return $this->canEdit($template, $user);
        }

        throw new LogicException('This code should not be reached!');
    }

    /**
     * Allow edit if in the same transaction group
     *
     * @param Template $template
     * @param User     $user
     *
     * @return bool
     */
    private function canEdit(Template $template, User $user)
    {
        return $user->getAccessGroup() === $template->getAccessGroup();
    }

}