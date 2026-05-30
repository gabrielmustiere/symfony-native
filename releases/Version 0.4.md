### 🚀 Release 0.4 - UI Refactoring & Icons Migration

Cette version apporte des améliorations majeures à l'interface utilisateur, une simplification de la navigation et une
migration complète vers un système d'icônes moderne.

**Nouveautés et modifications :**

* **Système d'icônes (Symfony UX Icon) :**
    * Migration complète de tous les SVG vers **Symfony UX Icon**.
    * Adoption du jeu d'icônes **Tabler** pour une esthétique cohérente.
    * Utilisation systématique de la **syntaxe composant Twig** (`<twig:ux:icon name="..." />`).
    * Mise à jour des icônes dans la sidebar, le header et les messages flash.

* **Interface & Navigation :**
    * Simplification de la sidebar : conservation uniquement des entrées **Dashboard** et **Settings**.
    * Nettoyage du menu utilisateur : suppression du lien "Your profile" pour ne garder que "Sign out".
    * Uniformisation du design desktop et mobile.

* **Expérience Utilisateur (UX) :**
    * Correction du contrôleur Stimulus pour les **messages flash**.
    * Les notifications se ferment désormais correctement (automatiquement ou via le bouton de fermeture).
    * Amélioration des animations d'entrée et de sortie des notifications.

* **Technique :**
    * Alignement des noms de contrôleurs Stimulus avec les conventions Symfony (conversion underscores en tirets).
    * Optimisation du template `base.html.twig` pour une meilleure maintenance.
