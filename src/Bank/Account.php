<?php

declare(strict_types=1);

namespace App\Bank;

/**
 * Compte bancaire de démo. Solde en centimes.
 */
final readonly class Account
{
    /**
     * @param list<Transaction> $transactions
     */
    public function __construct(
        public string $id,
        public string $name,
        public string $type,
        public string $iban,
        public int $balanceCents,
        public string $icon,
        public array $transactions = [],
    ) {
    }

    /**
     * IBAN masqué façon relevé : FR76 •••• •••• 4821.
     */
    public function maskedIban(): string
    {
        $compact = str_replace(' ', '', $this->iban);
        $prefix = substr($compact, 0, 4);
        $suffix = substr($compact, -4);

        return sprintf('%s •••• •••• %s', $prefix, $suffix);
    }
}
