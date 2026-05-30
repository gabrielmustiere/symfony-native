### 🚀 Release 1.0 - Developer Experience & AI Tooling

Cette version majeure modernise l'outillage de test, restructure la documentation et intègre des serveurs MCP pour l'assistance IA dans le workflow de développement.

**Nouveautés et modifications :**

* **Migration Panther → Playwright :**
    * Remplacement complet de **Symfony Panther** par **Playwright** pour les tests E2E.
    * Nouveau fichier de configuration `playwright.config.ts` (baseURL `https://template.wip`, exécution séquentielle).
    * Migration du test de login vers `tests/e2e/login.spec.ts` (TypeScript).
    * Ajout de `package.json` avec script `test:e2e` et dépendances Playwright.
    * Suppression de Panther, php-webdriver et bdi.

* **Serveurs MCP (Claude Code) :**
    * Création du fichier `.mcp.json` avec trois serveurs configurés :
        * **Symfony AI Mate** : accès au profiler Symfony, logs Monolog, services du container DI.
        * **Playwright MCP** : automatisation navigateur pour tests et debug.
        * **Chrome DevTools MCP** : interaction avec Chrome via DevTools Protocol.
    * Installation de `symfony/ai-mate`, `symfony/ai-monolog-mate-extension` et `symfony/ai-symfony-mate-extension`.
    * Configuration du répertoire `mate/` (config, extensions, sources custom).

* **Documentation :**
    * Restructuration complète de `CLAUDE.md` : remplacement du glossaire encyclopédique par une section **Architecture & Responsabilités** orientée décision (couches, arbre de décision, anti-patterns).
    * Enrichissement de la section **Conventions** (nommage, Doctrine, services).
    * Mise à jour du `README.md` avec les nouvelles fonctionnalités, commandes et section MCP.

* **Maintenance :**
    * Mise à jour des dépendances Composer (Symfony 8.0, PHPStan, PHP-CS-Fixer).
    * Suppression de `.env.test` (configuration déplacée dans `phpunit.dist.xml`).
    * Nettoyage de `phpunit.dist.xml` (retrait de la configuration Panther).
