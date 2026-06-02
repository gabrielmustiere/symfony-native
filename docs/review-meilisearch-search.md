# Review — Recherche de transactions adossée à Meilisearch

> Date : 2026-06-02
> Stack : symfony
> Périmètre : working tree + index (9 nouveaux fichiers, ~6 fichiers modifiés hors `composer.lock`, ~640 lignes)
> Référence d'intention : aucune (review standalone — pas de story `docs/story/` associée)

## Bloquants

_(aucun)_

## Importants

- [x] **[ARCHI] Divergence de source pour le compte courant** — *Confirmée volontaire (échelle démo recherche).* **Corrigé** : `generatedHistory()` (2000 lignes, désormais remplacé par `TransactionFactory`) et la constante `CURRENT_ACCOUNT_HISTORY` supprimés de `DemoBankProvider`. `currentAccountTransactions()` ne renvoie plus que les opérations curées `$recent` — toujours consommées par la home (`templates/bank/home.html.twig:84`, 4 dernières). Plus de code mort.
- [x] **[ROBUSTESSE] Aucune gestion d'indisponibilité Meilisearch** — **Corrigé** : `TransactionSearch::result()` attrape `CommunicationException`/`TimeOutException` → état vide + flag `unavailable`. Le template affiche « La recherche est momentanément indisponible » (barre de recherche conservée pour réessayer), au lieu d'un 500. Les `ApiException` (vraies erreurs logiques) restent propagées.
- [ ] **[CI] Tests sans service Meilisearch** — le job PHPUnit (`.github/workflows/ci.yml`) ne déclare pas de service Meilisearch et `.env.test` pointe vers `127.0.0.1:7700`. Aucun test fonctionnel actuel ne rend la page compte → pas de casse immédiate, mais tout futur test fonctionnel/E2E de la page compte échouera en CI (le job e2e n'a ni service ni étape `reindex`). Latent — à tracer.

## Mineurs

- [ ] **[SECU] Échappement de filtre incomplet** — `TransactionIndex::quote()` (`src/Search/TransactionIndex.php:113`) échappe `"` mais pas le backslash. Une valeur `category` (LiveProp writable) se terminant par `\` casse la chaîne de filtre → `ApiException` → 500. Pas d'exfiltration (`accountId` non-writable + props signées par UX), mais crash possible via payload forgé. Échapper `\` avant `"`.
- [ ] **[DOC] Incohérences de volume** — `Makefile` (`search-reindex`) dit « 2M transactions », `DemoBankProvider` parle de 2000, mais `ReindexTransactionsCommand::DEFAULT_COUNT = 10_000_000`. Aligner les libellés.
- [ ] **[UX] Compteur de résultats plafonné** — `getResultCount()` renvoie `totalHits` plafonné à `MAX_TOTAL_HITS = 5000`. Sur `courant` (~10M docs) la vue affiche « 5 000 opérations » sans indication de troncature. Acceptable mais trompeur.
- [ ] **[PERF] Calcul inutile des transactions du compte courant** — lié à l'ARCHI ci-dessus : si `account.transactions` n'est plus lu pour `courant`, `currentAccountTransactions()` est calculé pour rien à chaque appel provider.

## Points positifs

- **Séparation claire** : `MeilisearchClientFactory` (construction client) / `TransactionIndex` (gateway requêtes) / `TransactionFactory` (génération) / `TransactionDocument` (mapping pur). Respecte la règle « pas de logique de requête hors repository » transposée au store de recherche.
- **Mémoire constante** : génération par `\Generator` + ingestion NDJSON par lots de 50k → tient sur 10M docs sans saturation mémoire.
- **Optimisation settings-before-import** documentée et corrigée dans le diff (évite un reindex complet côté Meilisearch).
- **Tests unitaires ciblés** sur la logique pure (`MerchantCatalog`, `TransactionDocument` round-trip) sans dépendance réseau.
- **Intégration Turbo Drive soignée** : `data-turbo-permanent` avec `id` uniques, View Transitions dégradant proprement (WKWebView iOS < 18), réinit Flowbite idempotente sur `turbo:load`.

## Verdict

- Bloquants restants : 0 / 0
- Importants restants : 1 / 3 (ARCHI + ROBUSTESSE corrigés ; reste le CI latent)
- Statut : **READY TO COMMIT** — le seul important restant ([CI] absence de service Meilisearch dans le job PHPUnit) est latent (aucun test ne rend la page compte aujourd'hui) et à tracer, pas bloquant.

Validations post-correction : PHPStan level 9 OK, 19 tests unitaires verts, PHP-CS-Fixer clean.

Note : suppression de `resultSumCents` confirmée volontaire (somme non triviale sur des millions de hits Meilisearch) — pas un finding.

Prochaine étape : `/commit`. À tracer séparément : ajouter un service Meilisearch (+ étape reindex) aux jobs CI avant tout test fonctionnel/E2E de la page compte.

## Hors review (à vérifier en environnement réel)

- Lancer `make search-reindex` puis naviguer sur `/comptes/courant` : vérifier que la recherche/filtre/pagination « afficher plus » fonctionnent et que les ~5000 hits remontent triés par date desc.
- Confirmer le comportement de la page compte quand Meilisearch est arrêté (`docker compose stop meilisearch`).
