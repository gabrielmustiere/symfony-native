### 🚀 Release 0.5 - Email Testing & Mailpit Integration

Cette version introduit une fonctionnalité de test pour l'envoi d'e-mails et son intégration avec Mailpit pour le
développement local.

**Nouveautés et modifications :**

* **Fonctionnalité d'Email :**
    * Ajout d'une route de test `/test-email` dans `PageController` permettant de déclencher l'envoi d'un e-mail.
    * Configuration de `symfony/mailer` pour utiliser Mailpit en développement et en test.
* **Infrastructure & Docker :**
    * Validation de la stack Mailpit via Docker Compose (SMTP sur le port 1025, Interface Web sur le port 8025).
    * Mise à jour de la configuration de la base de données dans `.env` pour correspondre aux identifiants définis dans
      `compose.yaml`.
* **Tests Automatisés :**
    * Création de `MailpitTest.php` : un test fonctionnel qui vérifie l'envoi effectif de l'e-mail et sa réception par
      l'API de Mailpit.
    * Configuration de `MESSENGER_TRANSPORT_DSN=sync://` dans l'environnement de test pour garantir une exécution
      synchrone des e-mails lors des tests.
