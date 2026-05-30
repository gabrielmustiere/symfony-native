# Changelog

Toutes les modifications notables de ce projet sont documentées dans ce fichier.

Le format est basé sur [Keep a Changelog](https://keepachangelog.com/fr/1.1.0/),
et ce projet adhère au [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.6.0] - 2026-05-29

### Added
- Page Design System listant tous les composants UX Toolkit Flowbite 4 avec leurs variants et exemples interactifs.
- Intégration de `tales-from-a-dev/flowbite-bundle` comme form theme Symfony pour styler automatiquement les formulaires avec les classes Flowbite/Tailwind.

### Changed
- Alignement des tokens couleur sur les valeurs par défaut Flowbite (blue→violet, emerald→green, rose→red, orange→amber).
- Mise à jour de la typographie : Roboto (corps), Montserrat (display), PT Mono (mono) via Google Fonts.

## [1.5.1] - 2026-05-26

### Changed
- Uniformisation des cibles Makefile : `test-unit` → `phpunit`, `test-e2e` → `playwright`, `cs-fix` → `php-cs-fix`, `cs-check` → `php-cs-check`. Alias `lint` et `ci` clarifiés.

### Removed
- Worker `mate` retiré de `.symfony.local.yaml` (l'extension tourne désormais via MCP, pas via un worker Symfony CLI).

## [1.5.0] - 2026-05-26

### Added
- Intégration de Symfony UX Toolkit avec dépendances `tales-from-a-dev/twig-tailwind-extra` et `twig/html-extra`.
- Composant Twig `<twig:Button>` (`templates/components/Button.html.twig`) avec variants `html_cva` (brand, secondary, outline, ghost, etc.), tailles, formes et fusion intelligente via `tailwind_merge`.
- ADR-0001 documentant la stack front du template (Paper + Tailwind 4 + Flowbite 4 + UX Toolkit).

### Changed
- Page d'accueil (`templates/page/index.html.twig`) : boutons inline migrés vers `<twig:Button>`.

## [1.4.0] - 2026-05-26

### Added
- Design system "Paper" documenté dans `DESIGN.md` (tokens couleurs, typographie Roboto/Montserrat/PT Mono, radius, spacing).
- Intégration Flowbite 4 (drawer sidebar, toasts flash, datepicker) via importmap (`flowbite`, `@popperjs/core`, `flowbite-datepicker`).

### Changed
- Redesign complet des templates `base.html.twig`, `security/login.html.twig`, `page/index.html.twig`, `common/flash-messages.html.twig` autour du design system "Paper".
- Réécriture de `assets/styles/app.css` avec variables `@theme` issues du design system et `@source` Flowbite.
- Bumps de dépendances : Tailwind 4.3, Symfony UX 3.x (stimulus-bundle, icons, live-component, turbo), PHPUnit 13, phpdocumentor 6, ai-mate 0.9, Playwright 1.60.

## [1.3.0] - 2026-05-15

### Added
- Industrialisation du repo en template GitHub publiable (fichiers `.github/`, instructions de bootstrap, métadonnées de template).

## [1.2.0] - 2026-04-20

### Changed
- BREAKING : bascule de PostgreSQL vers SQLite (`var/data.db`). `DATABASE_URL` mis à jour dans `.env`, `.env.dev` et `.env.test`.
- Migrations régénérées pour les types SQLite (`INTEGER PRIMARY KEY AUTOINCREMENT`, `CLOB`).
- `compose.yaml` ne lance plus que Mailpit ; volume `db-dev-data` retiré.
- Cibles `Makefile` `db-create`, `db-drop`, `db-reset` adaptées au fichier SQLite.

### Removed
- Service `database` (PostgreSQL 18) et migration PostgreSQL `Version20260128202734.php`.
- Clés `identity_generation_preferences` et `dbname_suffix` PostgreSQL dans `config/packages/doctrine.yaml`.

## [1.1.0] - 2026-04-20

### Added
- Cible `Makefile init` couvrant `composer install`, `npm install`, `playwright install`, `tailwind:build`, `db-reset`.
- Cibles `Makefile` Docker (`up`, `down`), tests découpés (`test-unit`, `test-e2e`) et base (`db-create`, `db-drop`, `db-reset`, `migrate`, `migration`, `fixtures`).
- Worker `mate` dans `.symfony.local.yaml` pour démarrer automatiquement le serveur MCP `symfony/ai-mate`.

### Changed
- `make serve` lance désormais Symfony en arrière-plan (`symfony serve -d`).

### Removed
- Outillage GSD (`.claude/agents/gsd-*`, `.claude/commands/gsd/*`, `.claude/get-shit-done/`, hooks `gsd-*.js`).
- Skills Symfony locaux (`.agents/skills/symfony-*`, `skills-lock.json`) — le template s'appuie désormais sur les skills installés dans `~/.claude/`.

## [1.0.0] - 2026-03-17

### Added
- Tests E2E Playwright : `playwright.config.ts` (baseURL `https://template.wip`), `tests/e2e/login.spec.ts`, `package.json` avec script `test:e2e`.
- Serveurs MCP pour Claude Code (`.mcp.json`) : Symfony AI Mate (profiler, logs, services), Playwright MCP, Chrome DevTools MCP.
- Packages `symfony/ai-mate`, `symfony/ai-monolog-mate-extension`, `symfony/ai-symfony-mate-extension` et répertoire `mate/`.

### Changed
- BREAKING : remplacement de Symfony Panther par Playwright pour les tests E2E.
- `CLAUDE.md` restructuré : section Architecture & Responsabilités (couches, arbre de décision, anti-patterns) à la place du glossaire encyclopédique.
- `README.md` mis à jour avec les nouvelles commandes et la section MCP.

### Removed
- Symfony Panther, `php-webdriver`, `bdi` et configuration Panther dans `phpunit.dist.xml`.
- Fichier `.env.test` (configuration déplacée dans `phpunit.dist.xml`).

## [0.6.0] - 2026-01-29

### Added
- Entité `User` (`UserInterface`, `PasswordAuthenticatedUserInterface`) et `SecurityController` pour login/logout.
- Configuration `security.yaml` avec `form_login` et hachage de mot de passe.
- Templates de connexion (`templates/security/login.html.twig`).
- Utilisateur de test `admin@example.com` dans `AppFixtures`.
- Test E2E Panther `tests/Panther/LoginTest.php` validant le flux de connexion en navigateur réel.

## [0.5.0] - 2026-01-27

### Added
- Route `/test-email` (`PageController`) déclenchant un envoi via `symfony/mailer`.
- Test fonctionnel `MailpitTest.php` qui vérifie la réception de l'e-mail via l'API Mailpit.

### Changed
- `MESSENGER_TRANSPORT_DSN=sync://` en environnement de test pour exécuter les e-mails de façon synchrone.
- `.env` aligné sur les identifiants de `compose.yaml`.

## [0.4.0] - 2026-01-27

### Changed
- Migration de tous les SVG vers Symfony UX Icon (jeu d'icônes Tabler), avec syntaxe composant Twig `<twig:ux:icon name="..." />`.
- Sidebar simplifiée : seules `Dashboard` et `Settings` conservées ; menu utilisateur réduit à `Sign out`.
- Uniformisation desktop/mobile de la mise en page.
- Noms de contrôleurs Stimulus alignés sur les conventions Symfony (underscores → tirets).

### Fixed
- Notifications flash : fermeture automatique et via bouton désormais fonctionnelles ; animations d'entrée/sortie corrigées.

## [0.3.0] - 2026-01-26

### Added
- Symfony Panther pour les tests E2E avec support JavaScript, extension Panther dans PHPUnit et premier test fonctionnel.
- `bdi` (Browser Driver Installer) pour gérer les drivers de navigateur.
- DoctrineFixturesBundle pour la génération de données de test.

### Changed
- `phpunit.dist.xml` et `.env.test` adaptés à Panther.

## [0.2.0] - 2026-01-26

### Added
- PHPStan (niveau élevé) pour l'analyse statique et PHP-CS-Fixer pour un style de code cohérent.
- Template `base.html.twig` enrichi : sidebar responsive, header de navigation, zone de contenu principale.
- Composants UI Tailwind Elements.

### Changed
- `.gitignore` optimisé et dépendances de développement mises à jour.

## [0.1.0] - 2026-01-26

### Added
- Première version : Symfony 8.0 sur PHP 8.4.
- Stack locale : PostgreSQL 18 et Mailpit via Docker Compose.
- Frontend Tailwind CSS avec Symfony UX (Stimulus, AssetMapper).
- Automatisation via Symfony CLI (watch Tailwind, services Docker).

[Unreleased]: https://github.com/gabrielmustiere/symfony-template/compare/v1.6.0...HEAD
[1.6.0]: https://github.com/gabrielmustiere/symfony-template/compare/v1.5.1...v1.6.0
[1.5.1]: https://github.com/gabrielmustiere/symfony-template/compare/v1.5.0...v1.5.1
[1.5.0]: https://github.com/gabrielmustiere/symfony-template/compare/v1.4.0...v1.5.0
[1.4.0]: https://github.com/gabrielmustiere/symfony-template/compare/v1.3.0...v1.4.0
[1.3.0]: https://github.com/gabrielmustiere/symfony-template/compare/v1.2.0...v1.3.0
[1.2.0]: https://github.com/gabrielmustiere/symfony-template/compare/v1.1.0...v1.2.0
[1.1.0]: https://github.com/gabrielmustiere/symfony-template/compare/v1.0.0...v1.1.0
[1.0.0]: https://github.com/gabrielmustiere/symfony-template/compare/v0.6.0...v1.0.0
[0.6.0]: https://github.com/gabrielmustiere/symfony-template/compare/v0.5.0...v0.6.0
[0.5.0]: https://github.com/gabrielmustiere/symfony-template/compare/v0.4.0...v0.5.0
[0.4.0]: https://github.com/gabrielmustiere/symfony-template/compare/v0.3.0...v0.4.0
[0.3.0]: https://github.com/gabrielmustiere/symfony-template/compare/v0.2.0...v0.3.0
[0.2.0]: https://github.com/gabrielmustiere/symfony-template/compare/v0.1.0...v0.2.0
[0.1.0]: https://github.com/gabrielmustiere/symfony-template/releases/tag/v0.1.0
