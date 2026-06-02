import { Controller } from '@hotwired/stimulus';

/*
 * Anime l'« analyse » factice de la pièce d'identité (étape ③).
 *
 * Les trois états (inactif / analyse / vérifié) sont rendus côté serveur par le
 * LiveComponent ; ce contrôleur ne tient que le minuteur : dès que la zone de
 * scan apparaît dans le DOM, il déclenche après un délai la confirmation
 * (LiveAction `provideDocument`). Aucune mutation de classe côté client, donc
 * aucun conflit avec le morphing Live. Aucun fichier n'est traité.
 */
export default class extends Controller {
    static targets = ['scanner', 'confirm'];

    static values = {
        duration: { type: Number, default: 2400 },
    };

    scannerTargetConnected() {
        this.timeout = setTimeout(() => {
            if (this.hasConfirmTarget) {
                this.confirmTarget.click();
            }
        }, this.durationValue);
    }

    scannerTargetDisconnected() {
        this.clear();
    }

    disconnect() {
        this.clear();
    }

    clear() {
        if (this.timeout) {
            clearTimeout(this.timeout);
            this.timeout = null;
        }
    }
}
