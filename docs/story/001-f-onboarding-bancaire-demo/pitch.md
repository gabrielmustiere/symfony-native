# Démo d'onboarding bancaire — un step form vitrine qui sonne « vraie banque »

> Un parcours d'ouverture de compte en 4 étapes, 100 % factice et sans persistance, conçu pour épater à la fois un prospect non-technique (« c'est crédible ») et un œil technique (« c'est bien foutu, et c'est du Symfony »).

## Contexte

Le projet est un asset de démo commercial : une fausse app bancaire iOS (Hotwire Native) qui doit déclencher la réaction « attends, c'est une vraie app ?! » chez un décideur non-technique (cf. `docs/vision.md`). Les écrans cœur visés par la vision sont consultatifs (accueil comptes, détail d'opérations, profil).

Il manque un moment **interactif** capable de montrer la fluidité d'un parcours saisi à la main — et accessoirement de prouver, à un interlocuteur technique, que la stack moderne (LiveComponents, Symfony UX, Form, Stimulus, step form) permet de produire quelque chose de « sexy » sans bundler ni SPA. Un onboarding est le terrain idéal : multi-étapes, validation temps réel, upload, animations. Sans lui, la démo reste une succession d'écrans à faire défiler, jamais à manipuler.

C'est un **asset distinct** de la démo prospect cœur : il vit en parallèle, peut être montré seul, et tourne en boucle.

## Alignement vision

- **Problème adressé** : enrichit la preuve « on sait livrer du crédible » en ajoutant la dimension *interaction* (pas seulement consultation).
- **Audience servie** : sert le prospect non-technique (crédibilité bancaire) **et** ouvre explicitement une audience secondaire jusque-là « hors cible » dans la vision — l'interlocuteur technique, à qui l'on prouve la puissance de la stack.
- **Principes respectés** : « Façade assumée » (données 100 % bidon, zéro persistance), « Tient debout seule » (vitrine autonome en boucle), « Personnalité sobre et rassurante ».
- **Principes tendus** : le principe n°1 « le polish bancaire prime sur la prouesse technique » est mis sous tension par l'intention de showcase technique. Arbitrage assumé : la prouesse technique est un **bonus** pour l'audience secondaire, jamais au détriment du polish bancaire (exigence : chaque écran doit aussi sonner vrai).
- **Anti-objectif tendu** : la vision liste « pas d'inscription ». L'onboarding est un flux d'inscription *de façade* — pas une vraie création de compte. **À tracer** : amender `docs/vision.md` (ajout d'audience secondaire + nuance sur l'anti-objectif). Ce n'est **pas un pivot** : la démo bancaire cœur reste la boussole.
- **Impact North Star** : renforce le « c'est une vraie banque ?! » par un parcours manipulable ; ajoute un « et en plus c'est propre » pour l'œil technique.

## Utilisateurs concernés

Pas de rôles applicatifs (asset sans authentification ni multi-utilisateurs).

- **Visiteur de la démo (prospect non-technique)** — découvre un parcours d'ouverture de compte fluide et crédible, manette en main.
- **Visiteur de la démo (interlocuteur technique)** — observe la validation live, la conservation d'état entre étapes et les animations, et y lit la maîtrise de la stack.
- **Présentateur (commercial / dev qui démontre)** — lance et relance le parcours sans bidouille, et peut accélérer une saisie en live via un bouton « remplir un exemple ».

## User Stories

- En tant que **visiteur**, je veux ouvrir l'onboarding via une URL directe afin de démarrer la démo sans détour.
- En tant que **visiteur**, je veux renseigner mon identité, mes coordonnées, déposer une pièce d'identité et choisir une offre, étape par étape, afin de vivre un parcours d'ouverture de compte crédible.
- En tant que **visiteur**, je veux voir mes erreurs de saisie signalées en temps réel (email, téléphone, âge) afin de ressentir une app réactive et soignée.
- En tant que **visiteur**, je veux revenir à une étape précédente sans perdre ce que j'ai déjà saisi afin de naviguer librement dans le formulaire.
- En tant que **visiteur**, je veux voir une « analyse » animée de ma pièce d'identité afin d'avoir l'impression d'un vrai traitement bancaire.
- En tant que **visiteur**, je veux un écran de récapitulatif puis de succès animé en fin de parcours afin de conclure sur une note positive.
- En tant que **présentateur**, je veux un bouton « remplir un exemple » afin d'accélérer une démo en live.
- En tant que **présentateur**, je veux un bouton « Recommencer » en fin de parcours afin d'enchaîner les démonstrations.

## Règles métier

1. **Aucune persistance** : aucune donnée saisie n'est enregistrée (ni base, ni session longue) ; tout repart de zéro à « Recommencer » ou au rechargement.
2. **Aucune création de compte réelle** : le parcours ne crée pas d'entité, ne touche pas au domaine `Bank/` existant, n'envoie aucun email.
3. **4 étapes ordonnées** : ① Identité (civilité, nom, prénom, date de naissance, nationalité) → ② Coordonnées (email, téléphone, adresse) → ③ Pièce d'identité (upload simulé + analyse factice) → ④ Choix d'offre + récapitulatif/signature.
4. **Validation live réelle** : email bien formé, téléphone formaté, âge ≥ 18 ans, champs requis — vérifiés en temps réel avant de pouvoir avancer.
5. **État conservé** : revenir en arrière n'efface pas les saisies des étapes déjà remplies.
6. **Upload simulé** : à l'étape ③, l'action de « dépôt » déclenche une animation d'analyse factice ; aucun fichier n'est réellement traité, stocké ni envoyé.
7. **Données et libellés plausibles** : offres de compte, mentions et textes doivent sonner « vraie banque » (pas de lorem ipsum, pas de valeurs absurdes).
8. **Fin de parcours** : écran de succès animé avec bouton « Recommencer » ; le parcours est autonome et ne bascule pas vers l'app bancaire de démo.
9. **Mobile-first** : chaque étape et l'upload doivent être pleinement utilisables au doigt dans la WebView Hotwire Native iOS.

## Critères d'acceptation

- [ ] Une URL dédiée ouvre directement l'onboarding sur l'étape ① sans landing ni authentification.
- [ ] Les 4 étapes s'enchaînent avec une barre de progression et des transitions fluides.
- [ ] La validation des champs s'affiche en temps réel et bloque le passage à l'étape suivante tant qu'une saisie est invalide (email, téléphone, âge ≥ 18, requis).
- [ ] Revenir à une étape précédente conserve toutes les données déjà saisies.
- [ ] L'étape ③ joue une animation d'« analyse » de la pièce d'identité sans traiter de vrai fichier.
- [ ] L'étape ④ présente un choix d'offre crédible puis un récapitulatif des informations saisies et une action de « signature ».
- [ ] Un bouton « remplir un exemple » pré-remplit les champs avec des données plausibles.
- [ ] La fin de parcours affiche un écran de succès animé avec un bouton « Recommencer » qui réinitialise le parcours.
- [ ] Le parcours est intégralement utilisable au doigt sur l'app iOS (WebView), sans débordement ni élément hors d'atteinte.
- [ ] Aucune donnée n'est persistée et le domaine `Bank/` existant n'est pas modifié.

## Hors scope

- **Vraie création de compte / persistance** : aucune entité, aucune écriture base, aucune session longue.
- **KYC / conformité réelle** : pas de vraie vérification d'identité, pas de DSP2/RGPD/anti-fraude — c'est une façade.
- **Vraie caméra / vrai upload de fichier** : l'étape pièce d'identité est simulée.
- **Authentification, multi-utilisateurs, rôles, back-office** : exclus (cohérent avec la vision).
- **Intégration avec l'app bancaire de démo** : pas de bascule vers les écrans comptes existants ; l'asset reste autonome.
- **Emails / notifications transactionnels** : aucun envoi.
- **Android / publication store** : hors cible (iOS de démo uniquement).

## Impacts transverses

- **Multi-tenant** : non.
- **Multi-thème** : non (réutilise le design system « Paper »).
- **i18n / traduction** : non (FR uniquement, cohérent avec le reste de la démo).
- **API** : non.
- **Permissions** : inchangé — parcours public, hors firewall authentifié.
- **Emails / notifications** : non.
- **Migration de données** : non (aucune entité, aucun schéma touché).
- **Comportement par défaut** : l'onboarding n'est accessible que via sa route dédiée ; le reste de l'app est inchangé.

## Notes pour le plan technique

- Step form multi-étapes : explorer un **LiveComponent** porteur de l'état du parcours (étape courante + données saisies), avec validation temps réel via les contraintes Symfony Validator exposées au composant.
- Conservation d'état entre étapes sans persistance : à confirmer (état porté côté composant Live / propriétés exposées).
- Animations & transitions : Stimulus + Turbo / transitions CSS Tailwind ; barre de progression.
- Upload simulé + analyse factice : contrôleur Stimulus déclenchant une animation temporisée (pas de réel traitement de fichier).
- Bouton « remplir un exemple » : action Live qui injecte un jeu de données plausibles.
- Données de façade (offres, libellés) : fixtures/constantes crédibles côté serveur, sans toucher au domaine `Bank/`.
- Vérifier le rendu dans la WebView Hotwire Native iOS (zones tactiles, clavier, scroll).
- Amender `docs/vision.md` (audience secondaire + nuance anti-objectif) — tâche de doc, hors implémentation de la feature.

## Questions ouvertes

- **Liste des offres de compte à l'étape ④** : combien et lesquelles (ex: Essentiel / Confort / Premium) ? → à trancher en plan.
- **Mécanique exacte de l'upload simulé sur mobile** : tap unique déclenchant l'analyse vs faux sélecteur de fichier. → assumé : tap déclenchant l'analyse, à confirmer en plan.
- **Forme de la « signature »** : case à cocher de consentement vs geste de signature dessinée. → à trancher en plan.
