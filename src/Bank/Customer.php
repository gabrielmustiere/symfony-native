<?php

declare(strict_types=1);

namespace App\Bank;

/**
 * Titulaire de démo affiché dans l'app (profil, salutation, carte).
 */
final readonly class Customer
{
    public function __construct(
        public string $firstName,
        public string $lastName,
        public string $email,
        public string $phone,
        public string $customerNumber,
        public \DateTimeImmutable $memberSince,
    ) {
    }

    public function fullName(): string
    {
        return $this->firstName . ' ' . $this->lastName;
    }

    public function initials(): string
    {
        return mb_strtoupper(mb_substr($this->firstName, 0, 1) . mb_substr($this->lastName, 0, 1));
    }
}
