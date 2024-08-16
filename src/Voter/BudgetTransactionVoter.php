<?php

namespace App\Voter;

use App\Entity\BudgetTransaction;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class BudgetTransactionVoter extends Voter
{

    protected function supports(string $attribute, mixed $subject): bool
    {
        if (!$subject instanceof BudgetTransaction) {
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
    }
}