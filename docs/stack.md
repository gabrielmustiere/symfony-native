# Stack technique — symfony-native

> Dernière mise à jour : 2026-06-02 — cartographie factuelle de la stack. Chaque entrée est prouvée par un fichier du dépôt (source entre parenthèses) ou marquée _non renseigné_.

## Vue d'ensemble

Template Symfony 8 **mobile-first** : un monolithe full-stack Symfony rendu en HTML/Hotwire, packagé en applications natives iOS et Android via **Hotwire Native** (la WebView affiche l'app Symfony). Backend PHP 8.5, frontend sans bundler (AssetMapper + Tailwind 4), données en SQLite (zéro infra). C'est un squelette de démarrage : la cible d'hébergement de production et la distribution mobile ne sont pas encore définies.

| Couche | Techno principale |
|---|---|
| Langage(s) | PHP 8.5, Kotlin (JVM 11), Swift, TypeScript (config Playwright) |
| Backend | Symfony 8.0 |
| Frontend | AssetMapper + Symfony UX (Stimulus, Turbo) + Tailwind CSS 4 |
| Mobile | Hotwire Native iOS 1.2.2 / Android 1.2.8 |
| Données | SQLite |
| Ops | Symfony CLI (local), Docker (Mailpit dev uniquement) |
| DevOps | GitHub Actions |

## Langages & runtimes

- **PHP** `>=8.5`, épinglé `8.5` — source : `composer.json`, `.php-version`, `php.ini` (CI : `.github/workflows/ci.yml`)
- **Node.js** `22` (CI) / `22+` requis — source : `.github/workflows/ci.yml`, `README.md`
- **Kotlin / JVM** — `sourceCompatibility`/`targetCompatibility` Java 11 — source : `android/app/build.gradle.kts`
- **Swift** (app iOS Xcode) — source : `ios/poc-mobile.xcodeproj/project.pbxproj`
- **TypeScript** — uniquement pour la config de tests Playwright (`playwright.config.ts`) ; pas de TypeScript applicatif (AssetMapper sert du JS natif).

## Backend

- **Framework** : Symfony `8.0.*` full-stack (`framework-bundle`) — source : `composer.json`
- **ORM / données** : Doctrine ORM `^3.6.7` + DoctrineBundle `^3.2.2` + migrations `^4.0` — source : `composer.json`, `migrations/`
- **Libs structurantes** : Symfony Messenger (transport Doctrine), Security, Mailer, Serializer, Validator, Form, Translation, Notifier, HTTP Client, Expression Language — source : `composer.json`
- **Architecture** : `Controller → Service/Manager → Repository → Entity` ; QueryBuilder confiné aux repositories, pas de logique métier dans controller/entity — source : `CLAUDE.md`, arborescence `src/`
- **Découpage `src/`** : `Controller/`, `Entity/`, `Repository/`, `Enum/`, `Twig/`, `Bank/` (domaine bancaire de démo : `Account`, `Customer`, `Transaction`, `DemoBankProvider`), `Native/` (`AppNativeConfiguration.php`, intégration Hotwire Native côté serveur) — source : `src/`

## Frontend

- **Méthode d'assets** : Symfony AssetMapper + importmap — **pas de bundler JS** (ni Webpack ni Vite) — source : `importmap.php`, `composer.json` (`symfony/asset-mapper`)
- **CSS** : Tailwind CSS `^4.3.0` via `symfonycasts/tailwind-bundle` `^0.12` — source : `package.json`, `composer.json`
- **UI kit** : Flowbite `4.0.2` (drawer, toasts, datepicker) + Symfony UX Toolkit + `tales-from-a-dev/twig-tailwind-extra` (variants `html_cva`, `tailwind_merge`) — source : `package.json`, `importmap.php`, `composer.json`
- **JS interactif** : Symfony UX — Stimulus `3.2.2`, Turbo `7.3.0`, Live Component, Icons, ux-native — source : `importmap.php`, `composer.json`
- **Bridge natif** : `@hotwired/hotwire-native-bridge` `1.2.2` (communication WebView ↔ couche native) — source : `importmap.php`
- **Design system** : « Paper » — tokens (couleurs, typographies Roboto/Montserrat/PT Mono) dans `assets/styles/app.css` — source : `DESIGN.md`, `docs/adr/0001-stack-front-paper-flowbite-ux-toolkit.md`

## Mobile (Hotwire Native)

App mobile-first : la WebView charge l'application Symfony ; la couche native fournit navigation, splash screen et passkeys.

- **iOS** : app Xcode `poc-mobile`, bundle `$(PRODUCT_BUNDLE_IDENTIFIER)`, Hotwire Native iOS `1.2.2` (SPM) — source : `ios/poc-mobile.xcodeproj/.../Package.resolved`, `ios/Info.plist`
- **Android** : app Gradle/Kotlin `net.technao.poc_mobile`, minSdk 28 / targetSdk 36 / compileSdk 36, Hotwire Android (`dev.hotwire:core` + `navigation-fragments`) `1.2.8`, AGP `9.2.1`, `androidx.webkit` `1.14.0` — source : `android/app/build.gradle.kts`, `android/gradle/libs.versions.toml`
- **WebAuthn / passkeys** : Associated Domains `webcredentials:symfony-native.wip?mode=developer` (iOS), WebAuthn activé dans la WebView Android — source : `ios/poc-mobile/poc-mobile.entitlements`, historique git récent. Études de fiabilité et arbitrage bridge natif dans `docs/webauthn-*.md`.
- **Distribution** : _non renseigné_ — PoC en exécution locale uniquement (simulateur / device dev), pas de pipeline TestFlight ni Play Store (déclaratif utilisateur)

## Données & stockage

- **Base de données** : SQLite — dev `var/data.db`, test `var/data_test.db` — source : `.env`, `.env.test`, `.env.dev`
- **File / queue** : Symfony Messenger — transport Doctrine en dev (`doctrine://default`), `sync://` en test — source : `.env`, `.env.test`
- **Cache / sessions / recherche / stockage objet** : _aucun service externe_ (zéro infra par conception)

## Ops / Infrastructure

- **Serveur local** : Symfony CLI (proxy HTTPS `symfony-native.wip`) + workers gérés (`messenger:consume async`, `tailwind:build --watch`, `docker compose`) — source : `.symfony.local.yaml`
- **Conteneurisation** : Docker **uniquement pour Mailpit en dev** (capture e-mail, `axllent/mailpit`) — pas de Dockerfile applicatif — source : `compose.yaml`
- **Hébergement de production** : _non renseigné_ — template, cible non définie (déclaratif utilisateur)
- **CDN / reverse proxy** : _non renseigné_
- **Gestion des secrets** : `.env` / `.env.local` (`APP_SECRET`) ; mode prod via `make prod` (génère `APP_SECRET`, build minifié) — source : `Makefile`. Pas de gestionnaire de secrets externe.
- **Environnements** : `dev`, `test`, `prod` (bascule `make dev` / `make prod`) — source : `Makefile`, `.env.*`

## DevOps / CI-CD

- **Pipeline CI** : GitHub Actions, déclenché sur push/PR vers `main` — 3 jobs : **lint** (CS-Fixer dry-run + PHPStan), **test** (PHPUnit sur SQLite), **e2e** (Playwright/Chromium avec Mailpit en service, rapport uploadé en artefact) — source : `.github/workflows/ci.yml`
- **Tests** : PHPUnit `^13.1` (Unit + Functional), Playwright `1.60` (E2E, sélecteurs `data-test`, Chromium) — source : `composer.json`, `package.json`, `playwright.config.ts`, `phpunit.dist.xml`
- **Analyse statique / style** : PHPStan **level 9** (+ extensions doctrine, phpunit, symfony, strict-rules) ; PHP-CS-Fixer `^3.95` — source : `phpstan.dist.neon`, `.php-cs-fixer.dist.php`, `composer.json`
- **Hooks git / automatisation deps** : _aucun_ détecté (pas de Dependabot, pas de pre-commit) ; `composer bump-after-update` activé — source : `composer.json`
- **Déploiement** : _non renseigné_ — la CI ne déploie pas
- **Reproduction locale de la CI** : `make ci` (lint + tests unitaires) — source : `Makefile`

## Monitoring / observabilité

- **Erreurs** : _non renseigné_ (template ; déclaratif utilisateur)
- **Métriques / traces** : _non renseigné_
- **Logs** : Monolog (`symfony/monolog-bundle`) — logging applicatif Symfony, pas de centralisation externe — source : `composer.json`

## Outillage de développement local

- **Commandes QA / build** : tout passe par `make` et la CLI `symfony` (jamais `php` direct) — `make init`, `make start`, `make db-reset`, `make test`, `make test-e2e`, `make quality`, `make ci` — source : `Makefile`, `CLAUDE.md`
- **Services de dev** : Mailpit via Docker (`make start`) — UI sur `http://localhost:8027` — source : `compose.yaml`, `README.md`
- **Assistance IA (MCP)** : Symfony AI Mate (`./vendor/bin/mate serve`, accès profiler/logs/container) + MCP Playwright + MCP Chrome DevTools — source : `.mcp.json`, `mate/`, `AGENTS.md`

## Contraintes & dette de stack connues

- **Versions Symfony figées à `8.0.*`** : tout le socle Symfony est verrouillé sur la branche 8.0 (`extra.symfony.require`, `conflict: symfony/symfony`) — montées de version à piloter globalement — source : `composer.json`
- **PHP épinglé `8.5`** : version récente, à surveiller pour la compatibilité des dépendances — source : `.php-version`, `composer.json`
- **SQLite par conception** : adapté au template/dev ; un passage en production multi-process nécessitera un arbitrage SGBD (`/tech-plan` ou `/adr`) — source : `.env`
- **Mobile en PoC** : apps `poc-mobile` non packagées pour distribution ; bundle Android `net.technao.poc_mobile` à figer avant publication.
- **Dépendances AI Mate en `^0.9`** : libs `symfony/ai-*` en versions pré-stables (`require-dev`) — source : `composer.json`

## Changelog

- 2026-06-02 — Création — inventaire initial
