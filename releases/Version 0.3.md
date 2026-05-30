### 🚀 Release 0.3 - Testing & Fixtures

Cette version se concentre sur la mise en place de l'infrastructure de tests et l'ajout de données de démonstration via
les fixtures.

**Nouveautés et modifications :**

* **Tests et Qualité :**
    * Intégration de **Symfony Panther** pour les tests de bout en bout (E2E) et le support du JavaScript.
    * Configuration de l'extension Panther dans PHPUnit.
    * Ajout de **bdi** (Browser Driver Installer) pour gérer les drivers de navigateur.
    * Création d'un premier test fonctionnel avec Panther.
* **Données et Fixtures :**
    * Installation de **DoctrineFixturesBundle** pour la génération de données de test.
* **Documentation :**
    * Mise à jour de la roadmap dans le `README.md`.
    * Validation de la configuration **Editorconfig**.
* **Configuration :**
    * Mise à jour de `phpunit.dist.xml` et `.env.test` pour supporter Panther.
