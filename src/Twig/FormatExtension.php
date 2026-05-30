<?php

declare(strict_types=1);

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

/**
 * Formatage de présentation pour l'app bancaire de démo (montants et dates FR).
 */
final class FormatExtension extends AbstractExtension
{
    private const array MONTHS = [
        1 => 'janvier', 2 => 'février', 3 => 'mars', 4 => 'avril',
        5 => 'mai', 6 => 'juin', 7 => 'juillet', 8 => 'août',
        9 => 'septembre', 10 => 'octobre', 11 => 'novembre', 12 => 'décembre',
    ];

    /**
     * @return list<TwigFilter>
     */
    public function getFilters(): array
    {
        return [
            new TwigFilter('money', $this->formatMoney(...)),
            new TwigFilter('day_label', $this->formatDay(...)),
        ];
    }

    /**
     * Formate des centimes en euros façon relevé français : 4 826,57 €.
     * Avec $signed, préfixe explicitement le signe (+ pour les crédits).
     */
    public function formatMoney(int $cents, bool $signed = false): string
    {
        $amount = number_format(abs($cents) / 100, 2, ',', "\u{202F}");

        $sign = '';
        if ($cents < 0) {
            $sign = '−';
        } elseif ($signed && $cents > 0) {
            $sign = '+';
        }

        return $sign . $amount . "\u{202F}€";
    }

    /**
     * Libellé de jour en français : « 30 mai 2026 ».
     */
    public function formatDay(\DateTimeImmutable $date): string
    {
        return sprintf(
            '%d %s %d',
            (int) $date->format('j'),
            self::MONTHS[(int) $date->format('n')],
            (int) $date->format('Y'),
        );
    }
}
