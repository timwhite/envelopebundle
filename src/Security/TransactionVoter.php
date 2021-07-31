<?php


namespace App\Security;


use App\Entity\Transaction;
use App\Entity\User;
use LogicException;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class TransactionVoter extends Voter
{
    const EDIT = 'edit';
    const ATTRIBUTES = [self::EDIT];

    protected function supports($attribute, $subject)
    {
        if (!in_array($attribute, self::ATTRIBUTES)) {
            return false;
        }

        if (!$subject instanceof Transaction) {
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

        /** @var Transaction $transaction */
        $transaction = $subject;

        switch ($attribute) {
            case self::EDIT:
                return $this->canEdit($transaction, $user);
        }

        throw new LogicException('This code should not be reached!');
    }

    /**
     * Allow edit if in the same transaction group
     *
     * @param Transaction $transaction
     * @param User        $user
     *
     * @return bool
     */
    private function canEdit(Transaction $transaction, User $user)
    {
        return $user->getAccessGroup() === $transaction->getAccount()->getAccessGroup();
    }

}