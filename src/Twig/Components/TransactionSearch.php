<?php

declare(strict_types=1);

namespace App\Twig\Components;

use App\Bank\DemoBankProvider;
use App\Bank\Transaction;
use App\Enum\Type\TransactionCategory;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * Recherche et filtre réactifs sur les opérations d'un compte de démo.
 */
#[AsLiveComponent]
final class TransactionSearch
{
    use DefaultActionTrait;

    private const int PAGE_SIZE = 40;

    #[LiveProp]
    public string $accountId = '';

    #[LiveProp(writable: true, onUpdated: 'resetPagination')]
    public string $query = '';

    /** Valeur d'un TransactionCategory, ou '' pour toutes les catégories. */
    #[LiveProp(writable: true, onUpdated: 'resetPagination')]
    public string $category = '';

    #[LiveProp(writable: true)]
    public int $limit = self::PAGE_SIZE;

    /** @var list<Transaction>|null */
    private ?array $filteredCache = null;

    public function __construct(private readonly DemoBankProvider $bank)
    {
    }

    #[LiveAction]
    public function more(): void
    {
        $this->limit += self::PAGE_SIZE;
    }

    public function resetPagination(): void
    {
        $this->limit = self::PAGE_SIZE;
    }

    /**
     * @return list<TransactionCategory>
     */
    public function getCategories(): array
    {
        return TransactionCategory::cases();
    }

    public function getResultCount(): int
    {
        return \count($this->filtered());
    }

    public function getResultSumCents(): int
    {
        $sum = 0;
        foreach ($this->filtered() as $transaction) {
            $sum += $transaction->amountCents;
        }

        return $sum;
    }

    public function getHasMore(): bool
    {
        return $this->getResultCount() > $this->limit;
    }

    /**
     * Opérations affichées (tranche courante) regroupées par jour.
     *
     * @return list<array{date: \DateTimeImmutable, items: list<Transaction>}>
     */
    public function getGroups(): array
    {
        $groups = [];
        foreach (\array_slice($this->filtered(), 0, $this->limit) as $transaction) {
            $key = $transaction->date->format('Y-m-d');
            if (!isset($groups[$key])) {
                $groups[$key] = ['date' => $transaction->date, 'items' => []];
            }
            $groups[$key]['items'][] = $transaction;
        }

        return array_values($groups);
    }

    /**
     * @return list<Transaction>
     */
    private function filtered(): array
    {
        if (null !== $this->filteredCache) {
            return $this->filteredCache;
        }

        $account = $this->bank->account($this->accountId);
        $transactions = null !== $account ? $account->transactions : [];

        $query = trim($this->query);

        return $this->filteredCache = array_values(array_filter(
            $transactions,
            fn (Transaction $t): bool => $this->matches($t, $query),
        ));
    }

    private function matches(Transaction $transaction, string $query): bool
    {
        if ('' !== $this->category && $transaction->category->value !== $this->category) {
            return false;
        }

        if ('' === $query) {
            return true;
        }

        return false !== mb_stripos($transaction->label, $query)
            || false !== mb_stripos($transaction->category->label(), $query);
    }
}
