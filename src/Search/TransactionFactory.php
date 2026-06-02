<?php

declare(strict_types=1);

namespace App\Search;

use App\Bank\DemoBankProvider;
use App\Bank\MerchantCatalog;
use App\Bank\Transaction;
use App\Enum\Type\TransactionCategory;

/**
 * Génère, à mémoire constante (générateur), les documents à indexer :
 * un gros volume déterministe pour le compte courant + les opérations des
 * petits comptes lues depuis le DemoBankProvider.
 *
 * @phpstan-import-type Document from TransactionDocument
 */
final readonly class TransactionFactory
{
    private const string CURRENT_ACCOUNT = 'courant';

    /** Étalement des dates : ~6 ans, indépendant du volume demandé. */
    private const int DATE_SPAN_DAYS = 2190;

    /** @var list<string> */
    private const array SMALL_ACCOUNTS = ['livret-a', 'joint', 'assurance-vie'];

    public function __construct(
        private DemoBankProvider $bank,
        private MerchantCatalog $catalog,
    ) {
    }

    /**
     * @return \Generator<int, Document>
     */
    public function generate(int $currentAccountCount): \Generator
    {
        yield from $this->currentAccountDocuments($currentAccountCount);
        yield from $this->smallAccountDocuments();
    }

    /**
     * @return \Generator<int, Document>
     */
    private function currentAccountDocuments(int $count): \Generator
    {
        $merchants = $this->catalog->merchants();
        $merchantCount = \count($merchants);
        $start = new \DateTimeImmutable('2026-05-31');
        $perDay = max(1, intdiv($count, self::DATE_SPAN_DAYS) + 1);

        for ($i = 0; $i < $count; ++$i) {
            $date = $start->modify('-' . intdiv($i, $perDay) . ' day');
            $id = self::CURRENT_ACCOUNT . '_' . $i;

            if (0 === $i % 62) {
                yield TransactionDocument::fromTransaction(
                    new Transaction('Virement salaire — TECHNAO SAS', 328000, $date, TransactionCategory::Income),
                    self::CURRENT_ACCOUNT,
                    $id,
                );
                continue;
            }

            if (0 === $i % 49) {
                yield TransactionDocument::fromTransaction(
                    new Transaction('Remboursement Ameli', 1500 + ($i * 7) % 4000, $date, TransactionCategory::Income),
                    self::CURRENT_ACCOUNT,
                    $id,
                );
                continue;
            }

            [$label, $category, $base, $spread] = $merchants[($i * 7) % $merchantCount];
            $amount = $base + ($i * 131) % ($spread + 1);

            yield TransactionDocument::fromTransaction(
                new Transaction($label, -$amount, $date, $category),
                self::CURRENT_ACCOUNT,
                $id,
            );
        }
    }

    /**
     * @return \Generator<int, Document>
     */
    private function smallAccountDocuments(): \Generator
    {
        foreach (self::SMALL_ACCOUNTS as $accountId) {
            $account = $this->bank->account($accountId);
            if (null === $account) {
                continue;
            }

            foreach ($account->transactions as $i => $transaction) {
                yield TransactionDocument::fromTransaction($transaction, $accountId, $accountId . '_' . $i);
            }
        }
    }
}
