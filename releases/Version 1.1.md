### 🧹 Release 1.1 - Nettoyage outillage IA & Makefile DX

Cette version allège significativement l'outillage IA embarqué dans le template et enrichit le `Makefile` pour couvrir l'intégralité du cycle de développement local.

**Nouveautés et modifications :**

* **Suppression de l'outillage GSD (Get Shit Done) :**
    * Retrait complet des agents `.claude/agents/gsd-*` (18 agents).
    * Retrait des slash commands `.claude/commands/gsd/*` (60+ commandes).
    * Suppression du répertoire `.claude/get-shit-done/` (bin, templates, workflows, références).
    * Suppression des hooks `.claude/hooks/gsd-*.js` (check-update, context-monitor, prompt-guard, statusline, workflow-guard).
    * Nettoyage de `.claude/package.json`, `.claude/settings.json` et `.claude/gsd-file-manifest.json`.

* **Suppression des skills Symfony embarqués :**
    * Retrait du répertoire `.agents/skills/symfony-*` (API Platform, Doctrine, Messenger, tests, CQRS, etc.).
    * Suppression de `skills-lock.json`.
    * Le template ne surcharge plus les skills globaux du user — on s'appuie sur les skills installés dans `~/.claude/`.

* **Makefile enrichi :**
    * Nouvelle cible `init` complète : `composer install`, `npm install`, `playwright install`, `tailwind:build`, `db-reset`.
    * Cibles Docker : `up` / `down` (Postgres + Mailpit via `docker compose`).
    * Cibles base de données découpées : `db-create`, `db-drop`, `db-reset`, `migrate`, `migration`, `fixtures`.
    * Cibles tests : `test-unit` (PHPUnit) et `test-e2e` (Playwright).
    * `serve` lance désormais en arrière-plan (`symfony serve -d`).

* **Worker `mate` :**
    * Ajout du worker `mate` dans `.symfony.local.yaml` pour démarrer automatiquement le serveur MCP `symfony/ai-mate` via `symfony server:start`.
