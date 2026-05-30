# Vision — symfony-native

> Pitch en une phrase : une fausse app bancaire iOS, packagée en Hotwire Native par-dessus Symfony, qui sert d'asset de démo commercial pour convaincre un prospect non-technique qu'on peut lui livrer une app native crédible avec du web.

_Document vivant — enrichi au fil du cycle de vie, refondu lors d'un pivot stratégique. Date de dernière mise à jour : 2026-05-31._

## Changelog

Historique des évolutions structurantes (création, enrichissements, éditions ciblées, pivots). Lecture du haut vers le bas = ordre chronologique. Détails fins dans `git log`.

| Date | Nature | Axe | Motif |
|------|--------|-----|-------|
| 2026-05-31 | Création | — | Vision initiale |

## Le problème

En rendez-vous de vente, affirmer « on sait livrer du natif avec du web » ne convainc pas un décideur non-technique : un slide ou un argumentaire ne se touche pas. Il lui faut un artefact qu'il prend en main et qui *sonne vrai*.

La banque est choisie comme univers parce que c'est le **plus haut palier de crédibilité** (confiance, sécurité, sobriété, enjeu d'argent). Si une démo passe pour une vraie app bancaire entre les mains d'un prospect, elle passe *a fortiori* pour n'importe quel domaine moins exigeant.

**Comment c'est résolu aujourd'hui** : à coups de slides, de captures d'écran, ou en montrant un projet réel sous NDA qu'on ne peut pas toujours exhiber.
**Pourquoi c'est insuffisant** : rien de tout ça ne se prend en main ; le prospect ne *ressent* pas l'app, il l'imagine — et imaginer ne déclenche pas la conviction.
**Ampleur** : à chaque RDV où l'on vend une prestation d'app mobile bâtie sur cette stack.

## L'audience

### Utilisateur principal

- **Persona** : le **prospect / décideur non-technique** en rendez-vous commercial (dirigeant, responsable métier). Il juge à l'œil, manette en main, en quelques minutes.
- **Volume cible** : aucun — c'est un asset interne montré en one-to-one, pas un produit distribué.
- **Ce qui le bloque aujourd'hui** : il n'arrive pas à se projeter sur la qualité du livrable à partir d'un discours technique.

### Utilisateurs secondaires

- **Le commercial / toi qui présente** — doit pouvoir lancer la démo sur le device sans bidouille en live.

### Hors cible explicite

- **L'interlocuteur technique** (dev, CTO). Ce qui le rassure n'est pas la démo bancaire mais la preuve de faisabilité passkey en WKWebView, déjà tranchée par l'ADR-0002. La démo commerciale ne lui est pas destinée.

## La proposition de valeur

### Bénéfice utilisateur

Déclencher, en quelques minutes manette en main, la réaction spontanée *« attends, c'est une vraie app ?! »*. Le prospect passe de « je ne sais pas à quoi m'attendre » à « ils savent livrer du crédible ».

### Pourquoi nous, plutôt qu'eux

Au lieu de promettre, on fait toucher. Là où un concurrent montre des maquettes statiques, on tend un téléphone avec une app qui se manipule comme une vraie banque.

### Unfair advantage

La preuve technique (passkey / Face ID dans le WKWebView Hotwire Native) est **déjà banquée** (ADR-0002). Tout l'effort restant peut donc être investi dans le **polish bancaire**, pas dans la faisabilité.

### Séparation d'audiences (point central)

- **Passkey / Face ID** = preuve de *capacité technique*, déjà validée (ADR-0002). **Hors démo commerciale** : la démo prospect peut démarrer déjà connectée.
- **Univers bancaire** = preuve de *crédibilité*. C'est désormais le cœur du projet.

## Métriques de succès

### North Star

Qualitatif et assumé comme tel : la réaction *« c'est natif / c'est une vraie banque ?! »* observée en live chez le prospect. Se mesure par l'observation directe en rendez-vous.

### Seuils

- **Succès** : le parcours clé se déroule de bout en bout **sans que le présentateur ait à s'excuser** (pas de bug, pas d'écran vide, pas de « lorem ipsum », pas de latence gênante).

_Pas de métriques de funnel (acquisition / activation / rétention / monétisation) : c'est un asset interne one-shot, les vanity metrics SaaS ne s'appliquent pas._

### Signal d'arrêt

Si, en RDV test, un prospect non-technique repère que c'est une maquette ou exprime de la méfiance face à la fausse banque, et que le polish ne suffit pas à corriger l'impression, l'asset rate sa cible.

## Principes produit

1. **Le polish bancaire prime sur la prouesse technique** — chaque écran doit sonner vrai : chiffres plausibles, libellés d'opérations réalistes, dates cohérentes.
2. **Façade assumée** — données 100% bidon (fixtures crédibles), jamais une ligne de logique bancaire réelle.
3. **Profondeur > exhaustivité** — assez d'écrans pour donner l'épaisseur d'une vraie app (accueil/comptes, détail d'opérations, profil…), pas un clone complet d'une banque.
4. **Tient debout seule** — la démo ne dépend d'aucune bidouille faite en live ; elle se lance sur le device de démo et fonctionne.
5. **Personnalité sobre et rassurante** — les codes visuels de la confiance bancaire : sérieux, clarté, sécurité.

## Anti-objectifs

Ce qu'on **refuse explicitement** de faire, et pourquoi :

- **Aucun vrai backend bancaire ni transaction** — c'est une façade ; aucune intégration banque, aucune API de paiement, aucune vraie opération.
- **Aucune conformité réglementaire** (RGPD / DSP2 / KYC) — c'est une maquette, pas un produit ; zéro investissement compliance.
- **Pas d'Android, pas de store** — cible iOS (Simulateur / device de démo) uniquement ; aucune publication.
- **Pas de multi-utilisateurs** — un seul compte de démo scénarisé ; pas d'inscription, pas de back-office, pas de rôles.

## Hypothèses critiques

| # | Hypothèse | Comment l'invalider | Statut |
|---|-----------|---------------------|--------|
| 1 | Un décideur non-technique juge la crédibilité sur l'univers visuel/contenu, pas sur la fluidité technique | RDV test : noter ce qui accroche son œil | À tester |
| 2 | L'illusion « banque » tient sans backend réel ni biométrie montrée | Démo à blanc devant un tiers naïf | À tester |
| 3 | Montrer une *fausse* banque ne crée pas de méfiance (« pourquoi une fausse banque ? ») | Observer la réaction au cadrage de la démo | À tester |

## Risques externes

- **Reproductibilité de l'environnement de démo** : le test natif repose sur une procédure Simulateur fragile (cf. ADR-0002). Sortir la biométrie de la démo commerciale réduit ce risque — la démo peut tourner sur un device sans la bidouille entitlement.

## Horizons

### Court terme (one-shot)

Le projet est « fini » dès que le parcours clé bancaire se déroule sans accroc sur le device de démo : quelques écrans crédibles (accueil/comptes, détail d'opérations, profil), données bidon plausibles, navigation fluide.

### Anti-roadmap (refusé à court terme)

Même si c'est tentant : biométrie intégrée à la démo, virement « jouable », Android, multi-comptes. Tout cela alourdit sans servir le North Star.

## Notes pour les features à venir

Pointeurs bruts pour `/feature-pitch` (ne pas concevoir ici) :

- Écran d'accueil « mes comptes » avec soldes et liste de comptes.
- Détail d'un compte : flux d'opérations crédibles (libellés, montants, dates).
- Écran profil / paramètres pour donner l'épaisseur.
- Jeu de fixtures bancaires réalistes (noms d'enseignes, catégories de dépenses, périodicité).
