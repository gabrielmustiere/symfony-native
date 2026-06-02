<?php

declare(strict_types=1);

namespace App\Search;

use Meilisearch\Client;
use Meilisearch\Exceptions\ApiException;

/**
 * Gateway unique vers l'index Meilisearch des transactions.
 *
 * Joue le rôle de « repository » du store de recherche : aucune logique de requête
 * Meilisearch ne doit vivre ailleurs (contrôleur, composant Live…).
 */
final readonly class TransactionIndex
{
    private const string PRIMARY_KEY = 'id';

    /** Plafond de hits exposés : autorise le scroll « afficher plus » sur de gros volumes. */
    private const int MAX_TOTAL_HITS = 5000;

    /** Timeout d'attente des tasks (large : suppression/indexation de gros volumes). */
    private const int TASK_TIMEOUT_MS = 600_000;

    public function __construct(
        private Client $client,
        private string $indexName,
    ) {
    }

    /**
     * Supprime puis recrée l'index à vide (réindexation propre).
     */
    public function reset(): void
    {
        try {
            $task = $this->client->deleteIndex($this->indexName);
            $this->waitForTask((int) $task['taskUid']);
        } catch (ApiException) {
            // Index inexistant : rien à supprimer.
        }

        $task = $this->client->createIndex($this->indexName, ['primaryKey' => self::PRIMARY_KEY]);
        $this->waitForTask((int) $task['taskUid']);
    }

    /**
     * Ingestion d'un lot de documents au format NDJSON (1 objet JSON par ligne) —
     * chemin d'import le plus rapide de Meilisearch. Renvoie l'uid de la task.
     */
    public function addNdjsonBatch(string $ndjson): int
    {
        $task = $this->client->index($this->indexName)->addDocumentsNdjson($ndjson, self::PRIMARY_KEY);

        return (int) $task['taskUid'];
    }

    /**
     * Configure les attributs de recherche/filtre/tri. À appeler **avant** l'import
     * en masse : modifier ces settings sur un index déjà peuplé déclenche un reindex
     * complet (reco Meilisearch « configure settings first »). Posés en amont, les
     * documents sont indexés une seule fois, directement avec les bons réglages.
     * Renvoie l'uid de la task.
     */
    public function configure(): int
    {
        $task = $this->client->index($this->indexName)->updateSettings([
            'searchableAttributes' => ['label', 'categoryLabel'],
            'filterableAttributes' => ['accountId', 'categoryValue'],
            'sortableAttributes' => ['timestamp'],
            'displayedAttributes' => ['label', 'categoryValue', 'amountCents', 'timestamp'],
            'pagination' => ['maxTotalHits' => self::MAX_TOTAL_HITS],
        ]);

        return (int) $task['taskUid'];
    }

    /**
     * @return array{hits: list<array<string, mixed>>, totalHits: int}
     */
    public function search(string $accountId, string $query, string $category, int $limit): array
    {
        $filters = ['accountId = ' . self::quote($accountId)];
        if ('' !== $category) {
            $filters[] = 'categoryValue = ' . self::quote($category);
        }

        $result = $this->client->index($this->indexName)->search(trim($query) ?: null, [
            'filter' => $filters,
            'sort' => ['timestamp:desc'],
            'page' => 1,
            'hitsPerPage' => $limit,
        ]);

        /** @var list<array<string, mixed>> $hits */
        $hits = array_values($result->getHits());

        return [
            'hits' => $hits,
            'totalHits' => $result->getTotalHits() ?? $result->getHitsCount(),
        ];
    }

    /**
     * Bloque jusqu'à la fin de la task (timeout large pour les imports volumineux).
     */
    public function waitForTask(int $taskUid): void
    {
        $this->client->waitForTask($taskUid, timeoutInMs: self::TASK_TIMEOUT_MS, intervalInMs: 200);
    }

    private static function quote(string $value): string
    {
        return '"' . str_replace('"', '\\"', $value) . '"';
    }
}
