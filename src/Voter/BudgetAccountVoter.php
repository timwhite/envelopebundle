<?php

namespace App\Voter;

use App\Entity\BudgetAccount;
use App\Entity\BudgetTransaction;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class BudgetAccountVoter extends Voter
{
    const EDIT = 'budget_account_edit';

    protected function supports(string $attribute, mixed $subject): bool
    {
        if (!in_array($attribute, [self::EDIT])) {
            return false;
        }

        if (!$subject instanceof BudgetAccount) {
            return false;
        }

        return true;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }


        if ($user->getAccessGroup() !== $subject->getBudgetGroup()->getAccessGroup()) {
            return false;
        }

        return true;
    }
}