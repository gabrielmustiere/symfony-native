<?php

declare(strict_types=1);

namespace App\Search;

use App\Bank\Transaction;
use App\Enum\Type\TransactionCategory;

/**
 * Mapping pur entre une Transaction de démo et son document Meilisearch.
 *
 * @phpstan-type Document array{id: string, accountId: string, label: string, categoryValue: string, categoryLabel: string, amountCents: int, timestamp: int}
 */
final class TransactionDocument
{
    /**
     * @return Document
     */
    public static function fromTransaction(Transaction $transaction, string $accountId, string $id): array
    {
        return [
            'id' => $id,
            'accountId' => $accountId,
            'label' => $transaction->label,
            'categoryValue' => $transaction->category->value,
            'categoryLabel' => $transaction->category->label(),
            'amountCents' => $transaction->amountCents,
            'timestamp' => $transaction->date->getTimestamp(),
        ];
    }

    /**
     * @param array<string, mixed> $hit
     */
    public static function toTransaction(array $hit): Transaction
    {
        return new Transaction(
            self::asString($hit['label'] ?? ''),
            self::asInt($hit['amountCents'] ?? 0),
            (new \DateTimeImmutable())->setTimestamp(self::asInt($hit['timestamp'] ?? 0)),
            TransactionCategory::from(self::asString($hit['categoryValue'] ?? '')),
        );
    }

    private static function asString(mixed $value): string
    {
        return is_scalar($value) ? (string) $value : '';
    }

    private static function asInt(mixed $value): int
    {
        return is_numeric($value) ? (int) $value : 0;
    }
}
