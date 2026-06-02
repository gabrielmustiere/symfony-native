import { initFlowbite } from 'flowbite';

// Flowbite s'initialise normalement une seule fois au `DOMContentLoaded`.
// Turbo Drive ne rechargeant pas la page, les composants Flowbite injectés
// dans <main> (dropdowns, datepickers, modales…) cesseraient de fonctionner
// après la première navigation. On réinitialise donc à chaque `turbo:load`.
// initFlowbite est idempotent (registre d'instances interne), pas de doublon.
document.addEventListener('turbo:load', () => {
    initFlowbite();
    updateActiveNavLinks();
});

// Transition douce (cross-fade) entre les écrans via l'API View Transitions du
// navigateur. Turbo Drive remplace le <main> ; on met le rendu en pause pour
// l'envelopper dans document.startViewTransition(), qui anime old → new. Topbar
// et sidebar étant `data-turbo-permanent`, seul le contenu transitionne.
// Dégrade sans animation si l'API est absente (ex. WKWebView iOS < 18).
document.addEventListener('turbo:before-render', (event) => {
    if (!document.startViewTransition) {
        return;
    }

    event.preventDefault();
    document.startViewTransition(() => event.detail.resume());
});

// La sidebar étant `data-turbo-permanent` (non repeinte entre les navigations),
// l'état actif du menu est calculé côté client à partir de l'URL courante.
function updateActiveNavLinks() {
    const current = window.location.pathname;

    document.querySelectorAll('[data-nav-link]').forEach((link) => {
        const target = new URL(link.href).pathname;
        const isActive = target === '/' ? current === '/' : current.startsWith(target);

        link.classList.toggle('bg-gray-100', isActive);
        link.classList.toggle('dark:bg-gray-700', isActive);

        if (isActive) {
            link.setAttribute('aria-current', 'page');
        } else {
            link.removeAttribute('aria-current');
        }
    });
}
