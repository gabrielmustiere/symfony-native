# ADR-0003 — Faire fonctionner WebAuthn / passkeys dans la WebView Android Hotwire Native

- **Statut** : accepted
- **Date** : 2026-06-01
- **Déciders** : Gabriel (POC perso)
- **Story liée** : — (ADR standalone)

## Contexte

Le projet `symfony-native` est aussi packagé en app **Android** via **Hotwire Native** (`android/`, `startLocation = https://symfony-native.wip`). Après avoir validé WebAuthn dans le WKWebView iOS (cf. [ADR-0002](0002-test-passkeys-wkwebview-compte-apple-gratuit.md)), on veut la **parité Android** : prouver que l'invite passkey système s'ouvre **depuis la page web embarquée** (`/webauthn`), sans casser iOS ni le web.

Le point dur est que **la WebView Android n'est pas le WKWebView iOS**. Là où iOS expose nativement les passkeys (entitlement Associated Domains + AASA), la `WebView` Android **n'expose pas `PublicKeyCredential` par défaut** : le JS `webauthn_controller.js` afficherait simplement « Indisponible » sans tenter d'invite. Il faut activer explicitement le pont **`androidx.webkit` → Credential Manager** côté app, et déclarer l'association app ↔ domaine via un fichier **Digital Asset Links** (`assetlinks.json`) — l'équivalent Android de l'AASA.

## Decision drivers

- **D1 — Valider l'invite passkey *dans la WebView* Hotwire Native** : objectif du POC, qu'un test navigateur Chrome ne démontre pas.
- **D2 — Ne casser ni iOS ni le web** : le code partagé (`webauthn_controller.js`, route AASA) et le WebAuthn desktop doivent rester intacts ; les ajouts doivent être strictement additifs.
- **D3 — Sobriété & reproductibilité** : minimum de dépendances Android, procédure refaisable par un autre dev/agent, et symétrie lisible avec le flux iOS.

## Options considérées

### Option A — WebView Hotwire par défaut (ne rien activer)

Laisser la WebView telle quelle et espérer que WebAuthn fonctionne comme sur iOS.

- D1 : **non** — `PublicKeyCredential` est absent de la WebView Android par défaut ; l'invite ne s'ouvre jamais.
- Trade-off : rédhibitoire, ne répond pas à la question.

### Option B — Écran d'authentification natif (Kotlin + Credential Manager)

Court-circuiter la WebView : appeler `CredentialManager` directement en Kotlin pour create/get.

- D1 (rendu *dans la WebView*) : **non** — ça valide le Credential Manager natif, pas le passkey *depuis la page web embarquée*.
- D2 : indirectement respecté, mais **duplique** la logique déjà portée par le web et trahit l'approche web-first de Hotwire Native.
- D3 : plus de code natif à maintenir, asymétrique avec iOS.
- Trade-off : sur-conçu pour un POC web-first.

### Option C — Pont `androidx.webkit` + Digital Asset Links *(retenue)*

Activer le pont officiel WebView → Credential Manager et déclarer l'association via `assetlinks.json` :

1. Servir `assetlinks.json` publiquement côté Symfony (route + `security.yaml`).
2. Ajouter `androidx.webkit:webkit:1.14.0` et appeler `WebSettingsCompat.setWebAuthenticationSupport(settings, WEB_AUTHENTICATION_SUPPORT_FOR_APP)` sur la WebView Hotwire (gardé par `WebViewFeature.isFeatureSupported`).
3. Brancher la fabrique de WebView via `Hotwire.config.makeCustomWebView` dans une classe `Application`.

- D1 : **oui** — validé en live, l'invite passkey système s'ouvre dans la WebView sur `create()` puis `get()`.
- D2 : **oui** — côté serveur tout est additif (la route AASA et le JS partagé sont inchangés) ; côté Android tout est confiné à `android/`.
- D3 : **oui** — une seule dépendance ajoutée, runbook ci-dessous, symétrie nette avec iOS.
- Trade-off : dépend d'un **WebView provider récent (≥ 134) + Google Play** (émulateur AOSP exclu) ; le passkey est porté par le Credential Manager Google.

## Décision

**Option retenue : C — pont `androidx.webkit` + Digital Asset Links.**

C'est la seule option qui satisfait **D1 (invite dans la WebView)** tout en respectant **D2 (ne rien casser, additif)** et **D3 (sobriété)**. A échoue techniquement ; B contourne la WebView et trahit le web-first. Comme pour iOS, **le correctif serveur (`assetlinks.json` rendue publique) est un vrai fix permanent et légitime**, indépendant de l'environnement de test — il resservira pour un build de release.

Note de cohérence : le package Android a été renommé `com.example.poc_mobile` → **`net.technao.poc_mobile`** pour s'aligner sur le bundle iOS `net.technao.poc-mobile` (Android interdisant le tiret dans un package).

## Conséquences

**Positives**

- POC concluant : l'invite passkey native s'ouvre dans la WebView Hotwire Native — WebAuthn est viable sur Android comme sur iOS.
- Le fix `WellKnownController::assetLinks()` + règle `security.yaml` est **permanent** et resservira quel que soit le keystore (debug/release).
- Code Android propre et minimal : **une seule dépendance** (`androidx.webkit`), un sous-classement `HotwireWebView`, une `Application`.
- Symétrie iOS/Android lisible (AASA ↔ Digital Asset Links, entitlement ↔ `setWebAuthenticationSupport`).

**Négatives / coûts assumés**

- **Émulateur Google Play obligatoire** : image AOSP exclue (pas de Credential Manager) ; le WebView provider doit être **≥ 134** (à mettre à jour via le Play Store de l'émulateur).
- Le passkey est lié au **Credential Manager Google** (Google Password Manager) — pas de provider tiers testé.
- `mediation: "conditional"` n'est **pas supporté** par le pont WebKit (sans impact sur le JS actuel).
- L'empreinte SHA-256 servie est celle du **keystore debug** : un build de release nécessitera d'ajouter l'empreinte du keystore de release dans `assetlinks.json`.

**Suites possibles**

- [ ] **Pour un build release** : ajouter l'empreinte SHA-256 du keystore de release au tableau `sha256_cert_fingerprints` (les deux empreintes peuvent coexister). Envisager de servir `assetlinks.json` depuis un paramètre de conf plutôt qu'en dur.
- [ ] Conserver les règles `access_control` AASA **et** `assetlinks.json` dans `security.yaml` lors de tout remaniement de la sécurité (ne pas les perdre).

## Procédure reproductible (runbook)

Refaire le test passkey dans la WebView Android, à partir d'un état propre.

**Côté Symfony**
1. Route Digital Asset Links : `src/Controller/WellKnownController.php` sert `GET /.well-known/assetlinks.json` →
   ```json
   [{"relation":["delegate_permission/common.get_login_creds"],
     "target":{"namespace":"android_app","package_name":"net.technao.poc_mobile",
       "sha256_cert_fingerprints":["FE:73:...:6A"]}}]
   ```
2. `config/packages/security.yaml` — règle **avant** `^/` :
   ```yaml
   - { path: '^/\.well-known/assetlinks\.json$', roles: PUBLIC_ACCESS }
   ```
   Vérifier (doit renvoyer 200/JSON, **sans** redirection vers `/login`) :
   ```bash
   curl -sS -k -x http://127.0.0.1:7080 https://symfony-native.wip/.well-known/assetlinks.json
   ```

**Empreinte de signature**
3. Récupérer le SHA-256 du keystore (variant debug) et le recopier dans `assetlinks.json` :
   ```bash
   cd android && ./gradlew signingReport   # ligne "SHA-256" du variant debug
   ```

**Côté Android (`android/`)**
4. Dépendance : `androidx.webkit:webkit:1.14.0` (`gradle/libs.versions.toml` + `app/build.gradle.kts`).
5. `WebAuthnWebView` (sous-classe de `dev.hotwire.core.turbo.webview.HotwireWebView`) :
   ```kotlin
   if (WebViewFeature.isFeatureSupported(WebViewFeature.WEB_AUTHENTICATION)) {
       WebSettingsCompat.setWebAuthenticationSupport(
           settings, WebSettingsCompat.WEB_AUTHENTICATION_SUPPORT_FOR_APP)
   }
   ```
6. `PocMobileApplication` (classe `Application`) enregistre la fabrique :
   ```kotlin
   Hotwire.config.makeCustomWebView = { context -> WebAuthnWebView(context, null) }
   ```
   Déclarée dans `AndroidManifest.xml` via `android:name=".PocMobileApplication"`.
7. Build & run : `./gradlew installDebug` (ou Run depuis Android Studio).

**Côté émulateur**
8. Image **avec Google Play** (jamais AOSP).
9. **Android System WebView ≥ 134** : Play Store → mettre à jour « Android System WebView » (et Chrome).
10. Verrou d'écran + biométrie enrôlés (Settings → Security).
11. Résolution `.wip` : Wi-Fi → Proxy → `http://127.0.0.1:7080/proxy.pac`, et CA Symfony de confiance (déjà couverte par `network_security_config.xml`, `trust-anchors src="user"`).
12. Ouvrir `/webauthn` → l'invite passkey système s'ouvre sur `create()`, puis `get()`.

**Symptômes & causes**
- `PublicKeyCredential` absent / « Indisponible » → WebView provider trop ancien (< 134) : mettre à jour via le Play Store.
- Association refusée (l'invite ne s'ouvre pas) → empreinte SHA-256 erronée dans `assetlinks.json`, ou fichier injoignable depuis l'émulateur (vérifier proxy/CA et le `curl` ci-dessus).
- Aucun provider de passkey proposé → image sans Google Play, ou ajouter `androidx.credentials:credentials-play-services-auth` en repli.

## Links

- ADR lié : [ADR-0002](0002-test-passkeys-wkwebview-compte-apple-gratuit.md) (équivalent iOS).
- Mémoire projet : `ios-native-app-coordinates`, `mobile-first-hotwire-native`.
- Android — *Authenticate users with WebView* : https://developer.android.com/identity/sign-in/credential-manager-webview
- AndroidX WebKit — `WebSettingsCompat` : https://developer.android.com/reference/androidx/webkit/WebSettingsCompat
- Hotwire Native Android — *Configuration* : https://native.hotwired.dev/android/configuration
- Code : `src/Controller/WellKnownController.php`, `config/packages/security.yaml`, `assets/controllers/webauthn_controller.js`, `android/app/src/main/java/net/technao/poc_mobile/{WebAuthnWebView,PocMobileApplication}.kt`, `android/app/src/main/AndroidManifest.xml`.
