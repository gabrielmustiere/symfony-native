# Étude — WebAuthn mobile via Hotwire Native : fiabilité en production

- **Type** : étude de faisabilité / aide à la décision
- **Date** : 2026-06-01
- **Auteur** : Gabriel (POC perso)
- **ADR liés** : [ADR-0002](adr/0002-test-passkeys-wkwebview-compte-apple-gratuit.md) (iOS), [ADR-0003](adr/0003-webauthn-webview-android-hotwire-native.md) (Android)

## Question posée

L'approche **Hotwire Native + WebView** (une seule UI web packagée iOS et Android) est-elle une solution **fiable et utilisable en production** pour WebAuthn, avec l'exigence : **couvrir tous les gestionnaires de passkeys compatibles, quel que soit l'OS et le device** (iCloud Keychain, Google Password Manager, gestionnaires tiers type 1Password / Bitwarden / Dashlane, clés de sécurité, cross-device) ?

## Verdict

- **iOS : oui**, fiable et production-ready.
- **Android : « oui mais »** — viable sur device moderne, mais l'exigence « *tous* les gestionnaires, *peu importe* le device » n'est **pas garantie** en WebView embarquée, à cause de la fragmentation Android (pas à cause de Hotwire).
- **Aucune approche WebView ne garantit 100 % du parc Android.** La cible réaliste est « tous les devices raisonnablement modernes + repli (fallback) obligatoire ».

Principe de fond rassurant : **la WebView ne réalise pas WebAuthn elle-même, elle délègue au système de credentials de l'OS.** La couverture des gestionnaires = ce que l'OS supporte, pas ce que Hotwire supporte. Mais ce « délègue à l'OS » se comporte très différemment selon la plateforme.

## iOS (WKWebView) — fiable, production-ready

- Passkeys natifs dans le WKWebView depuis **iOS 16** via Associated Domains. Mature et stable.
- Sous le capot : `ASAuthorization` (le système). Tout ce que l'OS sait faire transite : **iCloud Keychain + fournisseurs tiers** (1Password, Dashlane… qui peuvent intercepter et prendre la priorité sur iCloud Keychain), **clés de sécurité USB/NFC**, **cross-device** (QR / flux hybride).
- Contrainte structurante : une WebView embarquée ne peut utiliser que les passkeys **du domaine RP de l'app** (authentification first-party, non fédérée) — ce qui correspond exactement au cas d'usage du projet.

**Conclusion iOS : l'architecture tient en production.**

## Android (WebView + pont `androidx.webkit`) — le maillon faible

C'est ici que l'exigence « peu importe le device » se fragilise. Faits concrets :

1. **WebAuthn n'est pas natif dans la WebView Android.** On s'en sort en « cassant » vers le **Credential Manager** (ce que fait le pont de l'ADR-0003). C'est récent et dépend d'un **WebView provider ≥ 134 + Google Play Services** → une part du parc (WebView ancien, ROM sans Google, certains OEM) **n'aura aucun support**.
2. **Pas d'UI conditionnelle** (autofill passkey) : `mediation: "conditional"` n'est **pas supporté** en WebView. On perd l'UX « saisis ton email → le passkey se propose automatiquement ». Le flux **modal** fonctionne, pas l'autofill.
3. **Gestionnaires tiers** (1Password, Bitwarden, Samsung Pass) via Credential Manager : disponibles **seulement à partir d'Android 14**, et **certains OEM ne les supportent toujours pas** même en 14. En dessous → **Google Password Manager uniquement**.
4. **Bug connu** : la création de passkey en WebView **ignore `excludeCredentials`** (issue `android/identity-samples#89`) → risque de **passkeys dupliqués**, à gérer/atténuer côté serveur.

**Conclusion Android : fonctionne sur device moderne récent ; « tous les gestionnaires, tous les devices » n'est pas garanti.**

## Le vrai trou, indépendant de la WebView

Le POC actuel n'a **aucune vérification serveur**. `assets/controllers/webauthn_controller.js` le dit explicitement : « Aucun backend… rien n'est vérifié côté serveur ». Le challenge est généré côté client, rien n'est stocké.

→ **Ce n'est pas de l'authentification, c'est une démo d'invite.** Le gros du travail « production » est là, et il est **indépendant du packaging mobile** : implémenter un vrai *Relying Party* serveur (génération et vérification du challenge, attestation/assertion, stockage des credentials). En Symfony : bundle **`web-auth/webauthn-lib`** (Spomky-Labs).

Note positive : en production, le contournement `.wip` du POC disparaît. Un **domaine HTTPS public réel** fait valider l'AASA et `assetlinks.json` normalement par les CDN Apple/Google. La prod est **plus simple** que le POC sur ce point précis.

## Trajectoires possibles

| Approche | Couverture | Coût | Pertinence |
|---|---|---|---|
| **A. WebView embarquée (état actuel)** | iOS ✅ / Android moderne ✅ mais fragmenté | Faible (1 seule codebase) | Si cible = devices récents + fallback mot de passe |
| **B. Hybride : étape d'auth promue en natif** via *bridge component* Hotwire (JS → écran natif appelant `ASAuthorizationController` / `CredentialManager` directement, résultat renvoyé au web) | **Maximale** : inclut l'UI conditionnelle et les gestionnaires tiers, contourne le plancher WebView Android | Moyen (un peu de natif iOS + Android) | **Voie production-grade si « peu importe le device » est un requirement ferme** |
| **C. Custom Tab (Android) / `ASWebAuthenticationSession` (iOS)** pour l'auth | Full WebAuthn navigateur | Moyen, casse le ressenti in-app + complexité de partage de session | Si l'on veut 100 % des features navigateur sans code natif |

## Recommandation

- **L'architecture Hotwire web-first reste le bon choix** : une seule UI web pour l'essentiel de l'app.
- Mais pour la **seule étape d'authentification**, si « fiable partout, tous les gestionnaires » est non négociable, **ne pas rester sur la WebAuthn-in-WebView pure côté Android** : promouvoir *uniquement cette étape* en natif via un **bridge component Hotwire** (option B). On garde le web partout ailleurs, on obtient la couverture complète + l'UI conditionnelle là où la WebView la refuse.
- **Quoi qu'il arrive** :
  - **Vrai RP serveur** (Spomky `web-auth/webauthn-lib`) — non négociable.
  - **Fallback** (mot de passe / magic link) quand WebAuthn est indisponible — sans cela, on exclut une partie du parc.

**En une phrase :** Hotwire est fiable et production-ready ; la WebAuthn-*dans-la-WebView* l'est sur iOS et « sous conditions » sur Android. Le POC a prouvé la faisabilité ; le pas vers la production, c'est le **RP serveur** + une **stratégie d'auth qui promeut le natif sur Android** plutôt que de tout miser sur le pont WebView.

## Prochaine décision structurante

Instruire l'**option B** (bridge component Hotwire pour l'auth : design JS + iOS + Android) et la comparer à l'option A dans un **ADR** dédié, avant tout passage en production.

## Sources

- Android — *Authenticate users with WebView* : https://developer.android.com/identity/sign-in/credential-manager-webview
- passkeys.dev — *Android* : https://passkeys.dev/docs/reference/android/
- passkeys.dev — *iOS & iPadOS* : https://passkeys.dev/docs/reference/ios/
- Corbado — *Native App Passkeys: Native vs. WebView* : https://www.corbado.com/blog/native-app-passkeys
- Corbado — *WebAuthn Conditional UI (Passkeys Autofill)* : https://www.corbado.com/blog/webauthn-conditional-ui-passkeys-autofill
- android/identity-samples — *WebView passkey creation ignores excludeCredentials* (#89) : https://github.com/android/identity-samples/issues/89
- Spomky-Labs / web-auth — *webauthn-lib* : https://github.com/web-auth/webauthn-framework
