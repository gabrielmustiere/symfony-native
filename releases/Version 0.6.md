### 🚀 Release 0.6 - User Authentication & Login Flow

Cette version introduit le système d'authentification des utilisateurs, comprenant la gestion de l'entité User, le
processus de connexion sécurisé et les tests de bout en bout avec Panther.

**Nouveautés et modifications :**

* **Authentification & Sécurité :**
    * Création de l'entité `User` avec support de `UserInterface` et `PasswordAuthenticatedUserInterface`.
    * Implémentation du `SecurityController` pour gérer la connexion et la déconnexion.
    * Configuration de `security.yaml` avec `form_login` et hachage des mots de passe.
    * Création des templates de connexion (`templates/security/login.html.twig`).
* **Fixtures :**
    * Mise à jour de `AppFixtures.php` pour inclure un utilisateur de test (`admin@example.com`).
* **Tests Automatisés :**
    * Ajout de `tests/Panther/LoginTest.php` pour valider le flux complet de connexion via un navigateur réel (Panther).
    * Nettoyage des tests d'exemple et migration vers des tests plus représentatifs de l'application.
* **Infrastructure :**
    * Mise à jour de `.env.test` et `phpunit.dist.xml` pour supporter les tests Panther.
    * Optimisation de la configuration Symfony local dans `.symfony.local.yaml`.
