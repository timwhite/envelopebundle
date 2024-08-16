<?php

namespace App\Voter;

use App\Entity\BudgetTransaction;
use App\Entity\Transaction;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Voter on (Account) Transactions. They must belong to your access group
 */
class TransactionVoter extends Voter
{
    const EDIT = 'transaction_edit';

    protected function supports(string $attribute, mixed $subject): bool
    {
        if (!in_array($attribute, [self::EDIT])) {
            return false;
        }

        if (!$subject instanceof Transaction) {
            return false;
        }

        return true;
    }

    /**
     * @param Transaction $subject
     */
    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        if ($subject->getAccount()->getAccessGroup() !== $user->getAccessGroup()) {
            return false;
        }

        return true;
    }
}