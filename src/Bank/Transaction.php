<?php

declare(strict_types=1);

namespace App\Bank;

use App\Enum\Type\TransactionCategory;

/**
 * Opération bancaire de démo. Montant en centimes (négatif = débit, positif = crédit).
 */
final readonly class Transaction
{
    public function __construct(
        public string $label,
        public int $amountCents,
        public \DateTimeImmutable $date,
        public TransactionCategory $category,
    ) {
    }

    public function isCredit(): bool
    {
        return $this->amountCents > 0;
    }
}
