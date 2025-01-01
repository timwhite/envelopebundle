<?php

namespace App\Service;

use App\Entity\Transaction;
use App\Repository\BudgetAccountRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;

class TNTClassifier
{
    protected ?\TeamTNT\TNTSearch\Classifier\TNTClassifier $classifier = null;

    public function __construct(private readonly Security $security,
        private readonly UserRepository $userRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly BudgetAccountRepository $budgetAccountRepository,
    ) {
    }

    public function suggestBudgetAccount(Transaction $transaction): string
    {
        if (!$this->classifier) {
            $user = $this->userRepository->getUserFromSecurity($this->security->getUser());
            $accessGroup = $user->getAccessGroup();
            // For some reason we need a refresh to use this access group
            $this->entityManager->refresh($accessGroup);
            $this->classifier = $accessGroup->getClassifier();
        }

        $result = $this->classifier->predict(self::prepareString($transaction->getFullDescription()));

        // Account ID
        // $this->budgetAccountRepository->getUserBudgetAccountByName($result['label'])?->getId()

        return $result['label'];
    }

    /**
     * @return string
     *
     * Do some replacements in the string to help the tokenizer
     */
    public static function prepareString($token): string
    {
        $replacements = [
            'INTER-BANK CREDIT' => 'INTER-BANK-CREDIT',
            'MISCELLANEOUS DEBIT:V' => 'MISCELLANEOUS-DEBIT-V',
            'MISCELLANEOUS DEBIT:' => 'MISCELLANEOUS-DEBIT:',
            'TRANSFER DEBIT' => 'TRANSFER-DEBIT',
            'TRANSFER CREDIT:' => 'TRANSFER-CREDIT:',
            'AUTOMATIC DRAWING:' => 'AUTOMATIC-DRAWING:',
            'EFTPOS DEBIT' => '	EFTPOS-DEBIT',
        ];

        return str_replace(
            array_keys($replacements),
            array_values($replacements),
            $token
        );
    }
}
