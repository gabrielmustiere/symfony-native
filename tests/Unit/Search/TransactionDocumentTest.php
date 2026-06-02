<?php

declare(strict_types=1);

namespace App\Tests\Unit\Search;

use App\Bank\Transaction;
use App\Enum\Type\TransactionCategory;
use App\Search\TransactionDocument;
use PHPUnit\Framework\TestCase;

final class TransactionDocumentTest extends TestCase
{
    public function testFromTransactionProducesIndexableDocument(): void
    {
        $transaction = new Transaction(
            'Carrefour Market — Lyon Part-Dieu',
            -3421,
            new \DateTimeImmutable('2026-05-30 12:00:00'),
            TransactionCategory::Groceries,
        );

        $document = TransactionDocument::fromTransaction($transaction, 'courant', 'courant_42');

        self::assertSame('courant_42', $document['id']);
        self::assertSame('courant', $document['accountId']);
        self::assertSame('Carrefour Market — Lyon Part-Dieu', $document['label']);
        self::assertSame('groceries', $document['categoryValue']);
        self::assertSame('Courses', $document['categoryLabel']);
        self::assertSame(-3421, $document['amountCents']);
        self::assertSame($transaction->date->getTimestamp(), $document['timestamp']);
    }

    public function testRoundTripPreservesSearchableData(): void
    {
        $original = new Transaction(
            'Virement salaire — TECHNAO SAS',
            328000,
            new \DateTimeImmutable('2026-05-02 09:30:00'),
            TransactionCategory::Income,
        );

        $restored = TransactionDocument::toTransaction(
            TransactionDocument::fromTransaction($original, 'courant', 'courant_0'),
        );

        self::assertSame($original->label, $restored->label);
        self::assertSame($original->amountCents, $restored->amountCents);
        self::assertSame($original->category, $restored->category);
        self::assertSame($original->date->getTimestamp(), $restored->date->getTimestamp());
        self::assertTrue($restored->isCredit());
    }
}
