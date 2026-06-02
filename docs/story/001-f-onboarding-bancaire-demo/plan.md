# Plan technique — Démo d'onboarding bancaire (step form vitrine)

> Pitch : `docs/story/001-f-onboarding-bancaire-demo/pitch.md`
> Stack : symfony

## Approche retenue

Un **unique LiveComponent `OnboardingFlow`** porte tout l'état du parcours (`#[LiveProp] step` + données saisies) et s'appuie sur **`ComponentWithFormTrait`** câblé sur **un seul `OnboardingType`** mappé à un **DTO `OnboardingData`** (objet PHP simple, aucune entité, aucune persistance). La validation multi-étapes utilise le mécanisme canonique des formulaires Symfony : des **`validation_groups` pilotés par l'étape courante** — au passage à l'étape suivante, seules les contraintes de l'étape active se déclenchent. L'état se conserve nativement entre étapes via l'hydratation/déshydratation des `LiveProp` (retour arrière = données intactes), sans session ni base. Le rendu reste 100 % au design « Paper » grâce à un **form theme Tailwind dédié** : `form_row` ressort le widget maison sur tous les champs (label/widget/erreurs écrits une fois). La couche « waouh » (barre de progression, transitions slide, animation d'analyse factice de la pièce d'identité) est portée par un contrôleur **Stimulus** ; les changements d'étape passent par des `LiveAction` (`next`/`prev`/`fillExample`/`restart`/`submit`) qui re-rendent juste le fragment courant (morphing Turbo). L'asset est **autonome** : route classique `/ouverture-de-compte` derrière le firewall existant, atteinte via un **CTA sur l'écran Comptes**, et un **layout immersif dédié** sans la barre d'onglets bancaire.

**Alternatives écartées** :

- **`ValidatableComponentTrait` + `LiveProp` annotées `#[Assert]` (sans Form)** : viable et proche de `TransactionSearch`, mais l'utilisateur privilégie le fonctionnement naturel de Symfony Form (types natifs `ChoiceType`/`DateType`/`CountryType`, `validation_groups` officiels) ; le form theme lève la seule réserve (le rendu).
- **Un FormType par étape (4 formulaires)** : multiplie les classes et complique le récap final + le « remplir un exemple » ; un seul Form à groupes de validation centralise l'état et les données.
- **`FileType` réel à l'étape ③** : interdit par le pitch (aucun traitement/stockage de fichier) ; remplacé par un tap déclenchant une animation et un booléen `documentFourni`.
- **Route publique hors firewall + ouverture de `/_components`** (telle que posée par le pitch) : écartée au profit d'une route authentifiée atteinte depuis le menu — plus simple (zéro config sécurité, pas de piège `/_components`) et plus pratique en démo live (présentateur déjà connecté). Divergence assumée vs pitch (cf. §Questions ouvertes / à répercuter via `/workflow:sync`).

## Entités et modèle de données

**Aucun impact modèle.** Aucune entité Doctrine, aucune table, aucune migration. Le parcours manipule un **DTO non persisté** `App\Onboarding\OnboardingData` (propriétés typées + contraintes `#[Assert\*]` réparties en groupes de validation), instancié et détruit dans le cycle de vie du LiveComponent. Le domaine `Bank/` existant n'est pas touché.

Champs du DTO `OnboardingData` (porté par le Form, jamais écrit en base) :

| Champ              | Type                | Groupe de validation | Contrainte                                             |
|--------------------|---------------------|----------------------|--------------------------------------------------------|
| `civility`         | `Civility` (enum)   | `etape1`             | `NotNull`                                               |
| `lastName`         | string              | `etape1`             | `NotBlank`, `Length(max: 80)`                           |
| `firstName`        | string              | `etape1`             | `NotBlank`, `Length(max: 80)`                           |
| `birthDate`        | `?\DateTimeImmutable`| `etape1`            | `NotNull`, `LessThanOrEqual('-18 years')` (âge ≥ 18)    |
| `nationality`      | string (code pays)  | `etape1`             | `NotBlank` (alimenté par `CountryType`)                 |
| `email`            | string              | `etape2`             | `NotBlank`, `Email`                                     |
| `phone`            | string              | `etape2`             | `NotBlank`, contrainte format FR (`Regex` ou `Length`)  |
| `addressLine`      | string              | `etape2`             | `NotBlank`                                              |
| `postalCode`       | string              | `etape2`             | `NotBlank`, `Regex` 5 chiffres                          |
| `city`             | string              | `etape2`             | `NotBlank`                                              |
| `documentProvided` | bool                | `etape3`             | `IsTrue` (le « dépôt » simulé doit avoir eu lieu)       |
| `offer`            | string (slug offre) | `etape4`             | `NotBlank`, `Choice(callback)` sur le catalogue         |
| `consent`          | bool                | `etape4`             | `IsTrue` (« signature » = case de consentement)         |

## Mécanismes framework mobilisés

- **Live Component (`#[AsLiveComponent]`) + `ComponentWithFormTrait`** : porte l'état du wizard et le Form ; re-render partiel par étape sans full reload (pattern déjà en place sur `TransactionSearch`).
- **Symfony Form + `validation_groups` dynamiques** : closure lisant `step` courant → valide uniquement l'étape active au clic « Suivant ». Mécanisme canonique des form multi-étapes.
- **`CountryType` (symfony/intl déjà installé)** : liste des nationalités native, France en `preferred_choices`.
- **`ChoiceType` (expanded)** : civilité (boutons) et offre (cartes radio) — rendu custom via le form theme.
- **Form theme Twig dédié** : surcharge des blocs `form_row`/`_widget`/`_errors` au design Paper, réutilisé sur tout le formulaire.
- **Contrôleur Stimulus** : transitions d'étape (CSS translate/opacity), barre de progression, animation temporisée d'« analyse » de la pièce d'identité (`setTimeout`, aucun fichier réel).
- **Service façade `OfferCatalog`** (pattern `DemoBankProvider`) : 3 offres crédibles + jeu de données plausible pour « remplir un exemple ».
- **Backed enum `Civility`** (`src/Enum/Type/`) : M./Mme avec `label()`.

## Fichiers à créer

| Fichier                                                  | Rôle                                                                                  |
|----------------------------------------------------------|----------------------------------------------------------------------------------------|
| `src/Controller/OnboardingController.php`                | Route `app_onboarding` (`/ouverture-de-compte`) ; rend la coquille hébergeant le composant. |
| `src/Onboarding/OnboardingData.php`                      | DTO non persisté du parcours : propriétés typées + `#[Assert\*]` groupés par étape.    |
| `src/Onboarding/OfferCatalog.php`                        | Façade des 3 offres (slug, libellé, prix, avantages) + jeu de données « remplir un exemple ». |
| `src/Form/OnboardingType.php`                            | Form unique : tous les champs typés + `validation_groups` pilotés par l'étape.         |
| `src/Enum/Type/Civility.php`                             | Backed enum M./Mme avec `label()`.                                                     |
| `src/Twig/Components/OnboardingFlow.php`                 | LiveComponent : `ComponentWithFormTrait`, `LiveProp step`, actions `next`/`prev`/`fillExample`/`restart`/`submit`. |
| `templates/onboarding/base.html.twig`                   | Layout immersif Paper (head + fonts), header sobre avec croix « Quitter », sans nav bancaire. |
| `templates/onboarding/index.html.twig`                  | Coquille étendant le layout, monte `<twig:OnboardingFlow/>`.                            |
| `templates/onboarding/_form_theme.html.twig`            | Form theme Tailwind « Paper » (blocs widget/label/erreurs).                             |
| `templates/components/OnboardingFlow.html.twig`         | Gabarit du composant : barre de progression + fragment d'étape + boutons de navigation. |
| `templates/components/OnboardingFlow/_step1.html.twig`  | Étape ① Identité.                                                                      |
| `templates/components/OnboardingFlow/_step2.html.twig`  | Étape ② Coordonnées.                                                                   |
| `templates/components/OnboardingFlow/_step3.html.twig`  | Étape ③ Pièce d'identité (dépôt simulé + zone d'analyse).                              |
| `templates/components/OnboardingFlow/_step4.html.twig`  | Étape ④ Offre + récapitulatif + consentement.                                          |
| `templates/components/OnboardingFlow/_success.html.twig`| Écran de succès animé (« Recommencer » + « Retour à l'accueil »).                       |
| `assets/controllers/onboarding_controller.js`           | Stimulus : progression, transitions slide, animation d'analyse factice temporisée.     |
| `tests/Functional/Onboarding/OnboardingFlowTest.php`    | Composant via `InteractsWithLiveComponents` : gating de validation par étape, fill/restart, submit sans persistance. |
| `tests/Unit/Onboarding/OfferCatalogTest.php`            | Catalogue : 3 offres exposées, jeu d'exemple cohérent.                                  |
| `tests/e2e/onboarding.spec.ts`                          | Playwright : happy path 4 étapes (mobile), retour-arrière conserve l'état, analyse, succès + Recommencer. |

## Fichiers à modifier

| Fichier                              | Modification                                                                          |
|--------------------------------------|---------------------------------------------------------------------------------------|
| `templates/bank/home.html.twig`      | Ajouter le CTA « Ouvrir un compte » (lien vers `app_onboarding`) en tête d'écran.     |

Aucune modification de `config/packages/security.yaml` (route sous le firewall `main` existant), ni de `bank/base.html.twig`.

## Impacts transverses

- **Multi-tenant** : non.
- **Multi-thème** : non (design « Paper » réutilisé via form theme + layout dédié).
- **API REST/GraphQL** : non.
- **i18n** : non (FR uniquement). Libellés en dur dans templates/Form.
- **Permissions** : firewall `main` existant suffit — le parcours vit dans l'app authentifiée, atteint depuis le menu. **Écart vs pitch** (qui posait « public, hors firewall ») assumé.
- **Emails / notifications** : non (aucun envoi).
- **Migration de données** : non (aucune entité, aucun schéma).
- **Comportement par défaut** : route accessible uniquement aux utilisateurs authentifiés ; le reste de l'app est inchangé ; aucune donnée saisie n'est conservée (reset au rechargement / « Recommencer »).

## Ordre d'implémentation

1. [ ] `OnboardingController` + route + `onboarding/base.html.twig` + `index.html.twig` rendant une coquille (vérifier l'accès et le layout immersif).
2. [ ] `Civility` (enum) + `OfferCatalog` (façade 3 offres + données d'exemple) + test unit du catalogue.
3. [ ] `OnboardingData` (DTO + contraintes groupées) + `OnboardingType` (champs typés + `validation_groups` par étape).
4. [ ] `OnboardingFlow` (LiveComponent) : `ComponentWithFormTrait`, `step`, actions `next`/`prev` avec gating de validation par étape + conservation d'état.
5. [ ] Gabarit composant + form theme Paper + validation live (`debounce`/`live#validate` au blur) — étapes ① et ②.
6. [ ] Étape ③ : dépôt simulé + contrôleur Stimulus d'animation d'analyse + bascule `documentProvided`.
7. [ ] Étape ④ : cartes radio d'offres + récapitulatif des saisies + case de consentement + action `submit` → état succès.
8. [ ] Actions `fillExample` (pré-remplissage) et `restart` (reset complet) + écran `_success`.
9. [ ] Polish : barre de progression + transitions slide (Stimulus/CSS) ; passe mobile-first/WebView (zones tactiles, clavier, scroll, safe-area).
10. [ ] CTA dans `bank/home.html.twig`.
11. [ ] Tests : functional composant (`InteractsWithLiveComponents`) + E2E Playwright happy path + retour-arrière.
12. [ ] QA finale : `make quality` (CS-Fixer + PHPStan level 9 + build) puis `make test` et `make test-e2e`.

## Stratégie de test

| Code                                          | Type        | Ce qu'on vérifie                                                                          |
|-----------------------------------------------|-------------|-------------------------------------------------------------------------------------------|
| `src/Onboarding/OfferCatalog.php`             | Unit        | 3 offres exposées (slug/libellé/prix/avantages) ; jeu « remplir un exemple » complet et valide. |
| `src/Twig/Components/OnboardingFlow.php`      | Functional  | `next` bloque tant que l'étape est invalide (email/âge/requis) et avance sinon ; `prev` conserve les données ; `fillExample` remplit ; `restart` réinitialise ; `submit` mène au succès sans persistance. |
| `src/Form/OnboardingType.php`                 | Functional  | `validation_groups` : seules les contraintes de l'étape courante se déclenchent (couvert via le composant). |
| `src/Controller/OnboardingController.php`     | Functional  | Route accessible authentifié, rend l'étape ① ; CTA depuis l'accueil pointe la bonne route. |
| Parcours complet                              | E2E (Playwright) | Happy path 4 étapes en viewport mobile ; retour-arrière conserve l'état ; animation d'analyse jouée ; succès + « Recommencer » réinitialise. Sélecteurs `data-test`. |

**Hors scope tests pour cette story** :

- Pas de test de persistance/base (aucune écriture par conception — on asserte plutôt l'**absence** d'effet de bord).
- Pas de test du vrai upload de fichier (étape ③ simulée).
- Pas de test cross-navigateur natif (rendu WebView vérifié manuellement en passe mobile-first).

## Risques et points d'attention

- **Validation par étape avec un Form unique** : la closure `validation_groups` doit recevoir le `step` courant (passé en option du Form à l'instanciation dans le composant). Risque de valider tout le formulaire au lieu de l'étape → mitigation : tester explicitement le gating étape par étape (étape ② invalide ne doit pas bloquer la validation de l'étape ①).
- **Conservation d'état entre étapes** : repose sur la (dé)hydratation des `LiveProp`/du DTO ; le DTO doit être hydratable proprement (types simples, enum `Civility` géré par le live serializer). Mitigation : test `prev` après saisie + vérifier la sérialisation de l'enum et de la date.
- **Animation d'analyse et morphing Turbo** : le re-render Live peut réinitialiser l'état Stimulus si le DOM est remplacé pendant l'animation. Mitigation : isoler l'animation côté Stimulus, déclencher la bascule `documentProvided` à la fin du timer, attributs `data-turbo-permanent`/clés stables si nécessaire.
- **Form theme Tailwind** : un thème incomplet peut casser le rendu d'un type de champ (date, country). Mitigation : couvrir les blocs des types réellement utilisés et valider visuellement chaque étape.
- **Mobile-first / WebView** : clavier natif, focus, scroll et safe-area peuvent masquer les boutons d'action. Mitigation : passe dédiée en simulateur iOS, boutons d'action en zone sûre (`env(safe-area-inset-bottom)`).

## Questions ouvertes

- **Mécanique exacte du dépôt simulé (étape ③)** : tap unique déclenchant l'analyse — **assumé** ; faux sélecteur de fichier non retenu (pas de `FileType`). → à confirmer au build si un visuel « caméra/scan » est souhaité.
- **Format du numéro de téléphone** : `Regex` strict FR vs `Length` souple — → tranché au build selon le rendu live souhaité.
- **Divergence accès vs pitch** : le pitch pose un asset **public hors firewall** ; le plan le place **derrière le firewall, accès par CTA**. → décision produit prise avec l'utilisateur ; à répercuter sur `pitch.md` via `/workflow:sync` après livraison (§Règles métier, §Impacts transverses, §Critères d'acceptation « sans authentification »).
