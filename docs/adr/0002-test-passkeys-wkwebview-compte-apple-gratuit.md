# ADR-0002 — Tester les passkeys WebAuthn en local dans le WKWebView Hotwire Native avec un compte Apple gratuit

- **Statut** : accepted
- **Date** : 2026-05-30
- **Déciders** : Gabriel (POC perso)
- **Story liée** : — (ADR standalone)

## Contexte

Le projet `symfony-native` est packagé en app iOS via **Hotwire Native** (`ios/poc-mobile`, `rootURL = https://symfony-native.wip`). On veut vérifier que **WebAuthn / passkeys** fonctionne **à l'intérieur du WKWebView** de l'app native, et pas seulement dans un navigateur de bureau — c'est l'enjeu du POC.

Or les passkeys dans un WKWebView exigent que l'app hôte porte l'entitlement **Associated Domains** (`webcredentials:<domaine>`) **et** qu'un fichier `apple-app-site-association` (AASA) soit servi sur le domaine. Deux contraintes bloquantes se sont révélées :

1. Le compte Apple disponible est un **compte gratuit (Personal Team)**, qui ne peut **pas provisionner** la capability Associated Domains — Xcode ne la propose même pas, et la retire au build.
2. Le domaine de dev est local (`.wip`, servi via le proxy Symfony), donc invisible du CDN Apple qui valide normalement l'AASA.

Sans solution, le test natif est impossible et l'on ne sait pas si WebAuthn est viable en Hotwire Native pour ce projet.

## Decision drivers

- **D1 — Valider le rendu passkey *dans le WKWebView* Hotwire Native** : l'objectif du POC est de prouver que l'invite système (Face ID / passkey) s'ouvre depuis la page web embarquée, ce qu'un test navigateur ne démontre pas.
- **D2 — Zéro coût / rester sur compte Apple gratuit** : ne pas dépenser 99 €/an tant que le POC n'est pas concluant.
- **D3 — Reproductibilité & propreté du repo** : la manip doit être refaisable par un autre dev/agent, et les correctifs légitimes doivent rester séparés des bidouilles jetables.

## Options considérées

### Option A — Test navigateur desktop uniquement

La page `/webauthn` (route `app_webauthn`, contrôleur Stimulus `webauthn_controller.js`) déclenche `navigator.credentials.create()/.get()`. Dans Safari/Chrome sur Mac, l'invite passkey s'ouvre **sans aucun prérequis** (pas d'entitlement, pas d'AASA).

- D1 (rendu WKWebView) : **non** — valide le code JS et l'UX, mais pas le comportement *dans l'app native*. C'est le cœur de ce qu'on veut vérifier, donc rédhibitoire.
- D2 (zéro coût) : oui.
- D3 (reproductibilité) : oui, trivial.
- Trade-off : ne répond pas à la question posée.

### Option B — Compte Apple Developer payant (99 €/an)

Adhésion au programme payant → la capability Associated Domains devient provisionnable, signature automatique, test possible sur **device réel** via la voie officielle Apple.

- D1 (rendu WKWebView) : oui, et même sur device physique (mieux que le Simulateur).
- D2 (zéro coût) : **non** — coût annuel + délai d'adhésion, pour un POC pas encore validé.
- D3 (reproductibilité) : oui, voie supportée et pérenne.
- Trade-off : la voie « propre », mais on paie et on attend avant même de savoir si l'approche tient.

### Option C — Bidouille Simulateur sous compte gratuit *(retenue)*

On contourne le blocage uniquement sur le **Simulateur** (où la signature n'est pas vérifiée comme sur device) :

1. Servir l'AASA publiquement côté Symfony (fix `security.yaml`).
2. Forcer l'entitlement en passant la signature du target en **manuelle** (Team/Profile/Cert = None), ce qui empêche Xcode de le retirer au build ad-hoc Simulateur.
3. Faire confiance à la CA Symfony **dans le Simulateur** + router `.wip` via le proxy PAC.
4. Enrôler une biométrie simulée.

- D1 (rendu WKWebView) : **oui** — l'invite passkey native s'ouvre effectivement dans le WKWebView (validé en live).
- D2 (zéro coût) : **oui** — reste sur compte gratuit.
- D3 (reproductibilité) : **partiel** — reproductible mais avec plusieurs étapes manuelles et fragiles (signature manuelle, re-build).
- Trade-off : **Simulateur uniquement**, fragile, et casse les builds device réel.

## Décision

**Option retenue : C — bidouille Simulateur sous compte gratuit.**

C'est la **seule option qui satisfait D1 (rendu dans le WKWebView) sous la contrainte D2 (compte gratuit)** : A ne démontre pas le comportement natif, B viole D2. Le coût sur D3 (procédure fragile et manuelle) est assumé car il s'agit d'un POC, et la trajectoire de sortie est claire (Option B le jour où un test device réel est requis).

Point notable : parmi les étapes de C, **le correctif `security.yaml` (AASA rendue publique) est un vrai fix permanent et légitime**, indépendant du compte — il bénéficiera aussi au flux payant futur. Seules la signature manuelle et l'injection d'entitlement relèvent de la bidouille Simulateur.

## Conséquences

**Positives**

- POC concluant : l'invite passkey native s'ouvre dans le WKWebView Hotwire Native — WebAuthn est viable pour ce projet.
- Le fix `config/packages/security.yaml` (exclusion de `/.well-known/apple-app-site-association` du firewall) est permanent et resservira quel que soit le type de compte.
- La page `/webauthn`, le `WellKnownController` et le JS sont du code propre et durable, indépendants de la bidouille.
- Procédure documentée (ce runbook + mémoire projet `ios-native-app-coordinates`).

**Négatives / coûts assumés**

- **Simulateur uniquement** : le test sur iPhone physique reste impossible sous compte gratuit (entitlement non provisionnable).
- La **signature manuelle** (Team/Cert = None) casse les builds device réel et doit rester ainsi ; réactiver *Automatically manage signing* re-supprime l'entitlement au build.
- L'entitlement n'est embarqué que via le build ad-hoc Simulateur : config non « CI/release-clean », à refaire manuellement après un clone neuf du projet iOS.
- Dépendances d'environnement à maintenir : CA Symfony installée dans le Simulateur, proxy PAC configuré côté Wi-Fi Simulateur, biométrie simulée enrôlée.

**Suites obligatoires**

- [ ] **Pour tester sur iPhone physique** : adhérer au programme Apple Developer payant (Option B), puis ré-ajouter Associated Domains via la capability Xcode et **restaurer la signature automatique** (ce qui rendra l'entitlement provisionnable proprement).
- [ ] Conserver la règle `access_control` AASA dans `security.yaml` lors de tout remaniement de la sécurité (ne pas la perdre).

## Procédure reproductible (runbook)

Refaire le test passkey dans le Simulateur, à partir d'un état propre.

**Côté Symfony**
1. Route AASA publique : `src/Controller/WellKnownController.php` sert `GET /.well-known/apple-app-site-association` → `{"webcredentials":{"apps":["2S4J753898.net.technao.poc-mobile"]}}` (en `application/json`).
2. `config/packages/security.yaml` — règle **avant** `^/` :
   ```yaml
   - { path: '^/\.well-known/apple-app-site-association$', roles: PUBLIC_ACCESS }
   ```
   Vérifier (doit renvoyer 200/JSON, **sans** redirection vers `/login`) :
   ```bash
   curl -sS -k -x http://127.0.0.1:7080 https://symfony-native.wip/.well-known/apple-app-site-association
   ```

**Côté Xcode (`ios/poc-mobile`)**
3. Fichier `ios/poc-mobile/poc-mobile.entitlements` :
   ```xml
   <key>com.apple.developer.associated-domains</key>
   <array><string>webcredentials:symfony-native.wip?mode=developer</string></array>
   ```
   Le `?mode=developer` force la récupération directe de l'AASA (le CDN Apple ne voit pas `.wip`).
4. Build Settings → `Code Signing Entitlements` = `poc-mobile/poc-mobile.entitlements`.
5. Signing & Capabilities → **décocher** *Automatically manage signing* (Team/Profile/Cert = None). Ignorer l'avertissement « requires a provisioning profile » qui concerne le *device*, pas le Simulateur.
6. Cible = un **simulateur iPhone** (jamais un device physique). Clean Build Folder + Build & Run.

**Côté Simulateur**
7. Faire confiance à la CA Symfony :
   ```bash
   xcrun simctl keychain booted add-root-cert "$HOME/Library/Application Support/symfony-cli/certs/rootCA.pem"
   ```
8. Résolution `.wip` : Réglages → Wi-Fi → (i) → Proxy → Automatique → `http://127.0.0.1:7080/proxy.pac`.
9. Biométrie : menu **Features → Face ID → Enrolled**, puis **Matching Face** lors de l'invite.

**Vérifications de diagnostic**
- Entitlement bien embarqué :
  ```bash
  codesign -d --entitlements :- "$(xcrun simctl get_app_container booted net.technao.poc-mobile)" | grep associated
  ```
- Validation du domaine par `swcd` (chercher l'échec `Code=104` / contenu inattendu si l'AASA n'est pas servie correctement) :
  ```bash
  xcrun simctl spawn booted log show --last 3m --predicate 'process == "swcd"' | grep symfony-native
  ```

**Symptômes & causes rencontrés**
- `NotAllowedError` immédiat → entitlement absent de l'app (Xcode l'a retiré : passer en signature manuelle).
- App tuée au lancement (`Process exited :<invalid>`) → entitlement injecté par re-signature ad-hoc *après* build, rejeté par AMFI : il faut l'embarquer **au build** (signature manuelle), pas en `codesign -f` post-build.
- `ASAuthorizationError Code=1004` + `swcd ... Code=104 "Failed to extract JSON object"` sur ~48 Ko → l'AASA renvoie en réalité la page de login (firewall) : appliquer le fix `security.yaml`.

## Links

- Mémoire projet : `ios-native-app-coordinates` (coordonnées app, prérequis WebAuthn).
- Apple — *Supporting associated domains* : https://developer.apple.com/documentation/xcode/supporting-associated-domains
- Apple — *Configuring an associated domain* : https://developer.apple.com/documentation/xcode/configuring-an-associated-domain
- Code : `src/Controller/WellKnownController.php`, `config/packages/security.yaml`, `assets/controllers/webauthn_controller.js`, `templates/page/webauthn.html.twig`, `ios/poc-mobile/poc-mobile.entitlements`.
