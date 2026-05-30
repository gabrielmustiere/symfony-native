import { Controller } from '@hotwired/stimulus';

/**
 * Démo client de l'API WebAuthn pour tester l'invite système (Face ID / passkey)
 * dans Hotwire Native (WKWebView iOS).
 *
 * Aucun backend : le challenge est généré côté client et rien n'est vérifié
 * côté serveur. On exerce uniquement navigator.credentials.create()/.get()
 * pour observer le comportement de l'authentificateur de la plateforme.
 */
export default class extends Controller {
    static targets = ['support', 'status', 'log', 'authButton'];

    connect() {
        this.credential = null;
        this.reportSupport();
    }

    async reportSupport() {
        const hasApi = typeof window.PublicKeyCredential !== 'undefined';
        if (!hasApi) {
            this.supportTarget.textContent = 'Indisponible — navigator.credentials / PublicKeyCredential absent.';
            this.supportTarget.dataset.state = 'ko';
            return;
        }

        let platform = false;
        try {
            platform = await window.PublicKeyCredential.isUserVerifyingPlatformAuthenticatorAvailable();
        } catch {
            platform = false;
        }

        this.supportTarget.textContent = platform
            ? 'API disponible · authentificateur de plateforme détecté (Face ID / Touch ID).'
            : 'API disponible · aucun authentificateur de plateforme (un passkey externe peut rester possible).';
        this.supportTarget.dataset.state = platform ? 'ok' : 'warn';
    }

    async register() {
        if (!this.ensureApi()) {
            return;
        }

        this.setStatus('Invitation à créer un passkey…', 'pending');

        const options = {
            challenge: this.randomBytes(32),
            rp: { name: 'Symfony Native Demo', id: window.location.hostname },
            user: {
                id: this.randomBytes(16),
                name: 'demo@symfony-native.test',
                displayName: 'Utilisateur démo',
            },
            pubKeyCredParams: [
                { type: 'public-key', alg: -7 },   // ES256
                { type: 'public-key', alg: -257 }, // RS256
            ],
            authenticatorSelection: {
                userVerification: 'preferred',
                residentKey: 'preferred',
            },
            timeout: 60000,
            attestation: 'none',
        };

        try {
            const credential = await navigator.credentials.create({ publicKey: options });
            this.credential = credential;
            this.authButtonTarget.disabled = false;
            this.setStatus('Passkey créé. Vous pouvez maintenant tester l\'authentification.', 'ok');
            this.appendLog('create()', {
                id: credential.id,
                type: credential.type,
                rawId: this.toBase64url(credential.rawId),
                transports: credential.response.getTransports?.() ?? [],
            });
        } catch (error) {
            this.fail('create()', error);
        }
    }

    async authenticate() {
        if (!this.ensureApi()) {
            return;
        }

        this.setStatus('Invitation à s\'authentifier…', 'pending');

        const options = {
            challenge: this.randomBytes(32),
            rpId: window.location.hostname,
            userVerification: 'preferred',
            timeout: 60000,
        };

        if (this.credential) {
            options.allowCredentials = [{
                type: 'public-key',
                id: this.credential.rawId,
            }];
        }

        try {
            const assertion = await navigator.credentials.get({ publicKey: options });
            this.setStatus('Authentification réussie.', 'ok');
            this.appendLog('get()', {
                id: assertion.id,
                type: assertion.type,
                userHandle: this.toBase64url(assertion.response.userHandle),
            });
        } catch (error) {
            this.fail('get()', error);
        }
    }

    ensureApi() {
        if (typeof window.PublicKeyCredential === 'undefined') {
            this.setStatus('WebAuthn n\'est pas supporté dans ce contexte.', 'ko');
            return false;
        }
        return true;
    }

    fail(step, error) {
        this.setStatus(`${step} a échoué : ${error.name} — ${error.message}`, 'ko');
        this.appendLog(`${step} · erreur`, { name: error.name, message: error.message });
    }

    setStatus(message, state) {
        this.statusTarget.textContent = message;
        this.statusTarget.dataset.state = state;
    }

    appendLog(label, payload) {
        const entry = document.createElement('div');
        entry.className = 'border-b border-gray-700 py-2 last:border-0';
        entry.innerHTML = `<div class="text-violet-400">${label}</div>`;
        const pre = document.createElement('pre');
        pre.className = 'whitespace-pre-wrap break-all text-gray-300';
        pre.textContent = JSON.stringify(payload, null, 2);
        entry.appendChild(pre);
        this.logTarget.prepend(entry);
    }

    randomBytes(length) {
        return crypto.getRandomValues(new Uint8Array(length));
    }

    toBase64url(buffer) {
        if (!buffer) {
            return null;
        }
        const bytes = new Uint8Array(buffer);
        let binary = '';
        bytes.forEach((b) => { binary += String.fromCharCode(b); });
        return btoa(binary).replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/, '');
    }
}
