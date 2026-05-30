<?php

declare(strict_types=1);

namespace App\Enum\Type;

enum TransactionCategory: string
{
    case Income = 'income';
    case Transfer = 'transfer';
    case Groceries = 'groceries';
    case Restaurant = 'restaurant';
    case Transport = 'transport';
    case Housing = 'housing';
    case Subscription = 'subscription';
    case Shopping = 'shopping';
    case Health = 'health';
    case Leisure = 'leisure';
    case Withdrawal = 'withdrawal';

    public function label(): string
    {
        return match ($this) {
            self::Income => 'Revenus',
            self::Transfer => 'Virement',
            self::Groceries => 'Courses',
            self::Restaurant => 'Restaurant',
            self::Transport => 'Transport',
            self::Housing => 'Logement',
            self::Subscription => 'Abonnement',
            self::Shopping => 'Achats',
            self::Health => 'Santé',
            self::Leisure => 'Loisirs',
            self::Withdrawal => 'Retrait',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Income => 'tabler:cash',
            self::Transfer => 'tabler:arrows-exchange',
            self::Groceries => 'tabler:shopping-cart',
            self::Restaurant => 'tabler:tools-kitchen-2',
            self::Transport => 'tabler:car',
            self::Housing => 'tabler:home',
            self::Subscription => 'tabler:repeat',
            self::Shopping => 'tabler:shopping-bag',
            self::Health => 'tabler:heart',
            self::Leisure => 'tabler:device-gamepad-2',
            self::Withdrawal => 'tabler:cash-banknote',
        };
    }
}
