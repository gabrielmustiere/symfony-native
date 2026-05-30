### 🗄️ Release 1.2 - Migration PostgreSQL → SQLite

Cette version simplifie drastiquement l'infrastructure locale en remplaçant PostgreSQL par SQLite. Le template ne nécessite plus de base de données conteneurisée : un simple fichier `var/data.db` suffit.

**Nouveautés et modifications :**

* **Bascule vers SQLite :**
    * `DATABASE_URL` passe à `sqlite:///%kernel.project_dir%/var/data.db` dans `.env`, `.env.dev` et `.env.test` (base de test : `var/data_test.db`).
    * Retrait du service `database` (PostgreSQL 18) et du volume `db-dev-data` dans `compose.yaml` — Docker ne sert plus qu'à Mailpit.
    * Nettoyage de `config/packages/doctrine.yaml` : suppression des `identity_generation_preferences` PostgreSQL et du `dbname_suffix` de test.

* **Migrations régénérées :**
    * Suppression de l'ancienne migration PostgreSQL `Version20260128202734.php`.
    * Nouvelle migration SQLite `Version20260420133408.php` créant les tables `user` et `messenger_messages` avec les types SQLite (`INTEGER PRIMARY KEY AUTOINCREMENT`, `CLOB`).

* **Makefile adapté :**
    * `db-create` crée le fichier `var/data.db` (au lieu d'invoquer `doctrine:database:create`).
    * `db-drop` supprime `var/data.db` et `var/data_test.db`.
    * `db-reset` enchaîne `db-drop` → `migrate` → `fixtures` (plus besoin de recréer la base).
    * Cible `up` documentée comme lançant uniquement Mailpit.

* **Documentation mise à jour :**
    * `CLAUDE.md` : stack mentionne SQLite (fichier `var/data.db`), la commande de reset DB utilise `rm -f var/data.db`.
    * `README.md` : prérequis Docker mentionné comme optionnel (Mailpit uniquement), instructions de reset simplifiées, accès BDD documenté via `sqlite3`.
