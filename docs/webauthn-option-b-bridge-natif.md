# Note de décision — WebAuthn mobile : option B (bridge natif) vs option C (pont WebView, implémentée)

- **Type** : aide à la décision (à arbitrer avec le CTO)
- **Date** : 2026-06-01
- **Auteur** : Gabriel (POC perso)
- **Statut** : à trancher — graduera en ADR une fois le choix fait
- **Liens** : [ADR-0002](adr/0002-test-passkeys-wkwebview-compte-apple-gratuit.md) (iOS), [ADR-0003](adr/0003-webauthn-webview-android-hotwire-native.md) (Android, option C retenue), [Étude fiabilité](webauthn-mobile-fiabilite-production.md)

## Pourquoi cette note

Le POC a validé WebAuthn **dans la WebView** Hotwire Native, sur iOS (ADR-0002) et Android (ADR-0003, **option C** : pont `androidx.webkit` + Digital Asset Links). L'étude de fiabilité conclut que cette approche est production-ready sur iOS et « oui mais » sur Android (fragmentation du provider WebView).

Cette note instruit l'**alternative B** — réaliser l'opération passkey en **code natif** (Kotlin / Swift) plutôt que dans la WebView — afin d'arbitrer : **garder C (déjà en place)** ou **investir dans B** avant la production.

⚠️ **Aucune des deux options ne change le serveur.** Symfony reste la source de vérité WebAuthn (challenge, vérification d'attestation/assertion). Le seul point qui diffère : **qui appelle l'API authenticator du système** — la WebView (C) ou du code natif (B).

## L'erreur à éviter sur B

Dans l'ADR-0003, l'option B est décrite comme un « écran d'authentification natif » qui **court-circuite** la WebView. Posée ainsi, elle duplique l'UI et trahit le web-first → rejetée à juste titre **pour le POC**.

Mais la bonne forme de B n'est pas un écran natif dupliqué : c'est un **bridge component Hotwire Native** (ex-Strada). La page web **reste le déclencheur** ; elle délègue juste l'opération authenticator à un petit composant natif, puis renvoie le résultat au serveur. C'est cette version qu'on instruit ici.

## Architecture de l'option B (bridge component)

Un seul contrat, trois morceaux :

**1. Web (partagé iOS + Android, écrit une fois)** — un contrôleur Stimulus « bridge » :
- navigateur pur → `navigator.credentials.create/get` (le code actuel, inchangé) ;
- dans l'app native → envoie les options WebAuthn (le JSON déjà produit par le serveur) au pont natif, attend la réponse, la POST au serveur.

**2. Android (Kotlin)** — `androidx.credentials:credentials` + `credentials-play-services-auth` :
`CredentialManager.createCredential(...)` / `getCredential(...)` avec le `requestJson` reçu du web.

**3. iOS (Swift)** — framework `AuthenticationServices` :
`ASAuthorizationPlatformPublicKeyCredentialProvider` + `ASAuthorizationController`.

Le format JSON des options/réponses est **le standard WebAuthn** des deux côtés → contrat web↔natif uniforme.

## Comparatif C (implémenté) vs B (potentiel)

| Critère | **C — pont WebView** *(en place)* | **B — bridge natif** *(potentiel)* |
|---|---|---|
| Serveur (RP) | identique | identique |
| Opération authenticator | la WebView, via `androidx.webkit` / WKWebView natif | code natif `CredentialManager` / `AuthenticationServices` |
| Code web partagé | `webauthn_controller.js` tel quel | contrôleur bridge partagé (1 fois) |
| Code natif à maintenir | 1 sous-classe WebView + 1 `Application` (Android) ; rien (iOS) | **1 composant Kotlin + 1 composant Swift** + contrat de pont |
| Fichiers d'association | `assetlinks.json` + AASA | `assetlinks.json` + AASA *(idem, pas supprimés)* |
| **Contrainte WebView Android ≥ 134 + Google Play** | **oui** (émulateur AOSP exclu) | **non** — dépend du Credential Manager système, pas du provider WebView |
| `mediation: "conditional"` / autofill | **non supporté** par le pont WebKit | **supporté** nativement des deux côtés |
| Couverture parc Android | dépend du provider WebView (fragmentation) | plus robuste (API système directe) |
| Effort | **fait** | à développer + tester sur 2 OS |
| Risque web-first | nul | faible si bridge (modéré si « écran natif ») |

## Ce que B débloque (et que C ne peut pas)

1. **Autofill / conditional UI** : la passkey proposée directement dans le champ de saisie. Non supporté par le pont WebKit (limite notée en ADR-0003). C'est un gain UX concret.
2. **Indépendance du provider WebView** : C exige Android System WebView ≥ 134 + Google Play. B s'appuie sur le Credential Manager de l'OS → meilleure couverture du parc Android (le « maillon faible » identifié dans l'étude fiabilité).
3. **Contrôle fin** des options natives (sélection passkey vs clé de sécurité, providers tiers).

## Ce que B coûte (tradeoffs assumés)

1. **Deux implémentations natives** (Kotlin **et** Swift) + le contrat de pont, là où C n'ajoute qu'une dépendance Android.
2. **Surface de maintenance** : un changement d'UX d'auth peut toucher 3 endroits (web + 2 natifs) au lieu d'un.
3. **B n'enlève aucun prérequis de C** : fichiers d'association et config serveur restent nécessaires — B **ajoute** par-dessus.
4. Sortie partielle du « web-first pur » : une étape du flux vit en natif.

## Critères d'arbitrage (pour la discussion CTO)

Choisir **B** si l'un de ces besoins est ferme :
- l'**autofill/conditional UI** fait partie de l'UX cible ;
- la cible inclut un **parc Android large/hétérogène** où l'on ne peut pas garantir WebView ≥ 134 ;
- on veut **éliminer la dépendance** au provider WebView et au Google Play de l'appareil.

Rester sur **C** si :
- la cible est un **parc moderne** et l'UX « bouton passkey » (sans autofill) suffit ;
- on veut **minimiser le code natif** et préserver le web-first strict ;
- la priorité est de livrer vite sur l'existant déjà validé.

**Note** : C et B ne sont pas exclusifs dans le temps. C est livrable maintenant ; B peut être instruit ensuite **sans jeter C** (le serveur et le web partagé restent communs). Une trajectoire raisonnable : **livrer C + fallback obligatoire**, puis migrer vers B si l'autofill ou la couverture Android deviennent bloquants.

## Prérequis communs aux deux options (rappel)

Quel que soit le choix, la production exige (cf. étude fiabilité) :
- un **vrai RP serveur** (`web-auth/webauthn-lib`), non négociable ;
- un **fallback** (mot de passe / magic link) quand WebAuthn est indisponible.

## Sources

- Android — *Authenticate users with WebView* : https://developer.android.com/identity/sign-in/credential-manager-webview
- Android — *Credential Manager* (`androidx.credentials`) : https://developer.android.com/identity/sign-in/credential-manager
- Apple — *AuthenticationServices / Public-Key Credentials* : https://developer.apple.com/documentation/authenticationservices
- Hotwire Native — *Bridge components* : https://native.hotwired.dev/overview/bridge-components
- Corbado — *Native App Passkeys: Native vs. WebView* : https://www.corbado.com/blog/native-app-passkeys
- Corbado — *WebAuthn Conditional UI (Passkeys Autofill)* : https://www.corbado.com/blog/webauthn-conditional-ui-passkeys-autofill
