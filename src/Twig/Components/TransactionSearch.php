<?php

declare(strict_types=1);

namespace App\Twig\Components;

use App\Bank\Transaction;
use App\Enum\Type\TransactionCategory;
use App\Search\TransactionDocument;
use App\Search\TransactionIndex;
use Meilisearch\Exceptions\CommunicationException;
use Meilisearch\Exceptions\TimeOutException;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

/**
 * Recherche et filtre réactifs sur les opérations d'un compte, adossés à Meilisearch.
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

    /** @var array{hits: list<array<string, mixed>>, totalHits: int}|null */
    private ?array $resultCache = null;

    /** Vrai si Meilisearch n'a pas pu être joint (conteneur arrêté, timeout). */
    private bool $unavailable = false;

    public function __construct(private readonly TransactionIndex $index)
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
        return $this->result()['totalHits'];
    }

    /**
     * Vrai si la recherche n'a pas pu aboutir faute de joindre Meilisearch.
     */
    public function isUnavailable(): bool
    {
        $this->result();

        return $this->unavailable;
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
        foreach ($this->result()['hits'] as $hit) {
            $transaction = TransactionDocument::toTransaction($hit);
            $key = $transaction->date->format('Y-m-d');
            if (!isset($groups[$key])) {
                $groups[$key] = ['date' => $transaction->date, 'items' => []];
            }
            $groups[$key]['items'][] = $transaction;
        }

        return array_values($groups);
    }

    /**
     * @return array{hits: list<array<string, mixed>>, totalHits: int}
     */
    private function result(): array
    {
        if (null !== $this->resultCache) {
            return $this->resultCache;
        }

        try {
            return $this->resultCache = $this->index->search(
                $this->accountId,
                $this->query,
                $this->category,
                $this->limit,
            );
        } catch (CommunicationException|TimeOutException) {
            // Meilisearch injoignable : on dégrade vers un état vide explicite
            // plutôt que de renvoyer une 500. Les erreurs applicatives
            // (ApiException) restent propagées, ce sont de vrais bugs.
            $this->unavailable = true;

            return $this->resultCache = ['hits' => [], 'totalHits' => 0];
        }
    }
}
