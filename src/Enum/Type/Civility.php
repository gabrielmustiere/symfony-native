<?php

declare(strict_types=1);

namespace App\Enum\Type;

enum Civility: string
{
    case Madame = 'mme';
    case Monsieur = 'm';

    public function label(): string
    {
        return match ($this) {
            self::Madame => 'Madame',
            self::Monsieur => 'Monsieur',
        };
    }

    public function short(): string
    {
        return match ($this) {
            self::Madame => 'Mme',
            self::Monsieur => 'M.',
        };
    }
}
