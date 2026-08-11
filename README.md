# Corpus de poésie — poesie.serviceproi.fr

Site de diffusion d'un corpus de textes (poésie, chansons, satire) hébergé sur o2switch.

## Architecture

- **SOURCE** — Google Drive (jamais modifié, jamais dans ce dépôt).
- **CATALOGUE** — base MySQL `nare8592_poesie`, table `documents` + `categories`/`series` (métadonnées et référencement).
- **CORPUS DE TRAVAIL** — `corpus/textes/INV-XXXX.txt` (116 fichiers) + `corpus/catalogue.json`, déployés sur le serveur hors docroot (`poesie-corpus-prive/`), jamais publics directement.
- **APPLICATION** :
  - `admin/` — administration éditoriale : CRUD textes/catégories, soumissions. Protégée par des comptes nominatifs en base (table `users`, rôle `admin` = contrôle total, `lecteur` = consultation seule bloquée côté serveur sur toute mutation) — voir `admin/login.php`/`admin/utilisateurs.php`. Remplace depuis le 2026-08-11 l'ancienne authentification HTTP Basic partagée (`.htaccess`/`.htpasswd`, identifiants dans `~/.config/o2switch/poesie-admin.env` — devenue obsolète, `.htaccess` supprimé du serveur).
  - `site/` — site public de diffusion : `accueil.php`, `liste.php`, `categorie.php`, `series.php`, `fiche.php`, `contributions.php`, `contribution.php`, `proposer.php`, `_layout.php`.
  - `app/_db.php` — helper de connexion partagé par `admin/` et `site/`.
- `scripts/` — scripts ponctuels de mise en place du schéma (déjà exécutés en production, conservés comme référence).
- `backups/` — sauvegardes logiques (dump SQL) prises avant chaque migration de schéma.
- `config/db.php.example` — modèle de configuration DB. Le fichier réel (`config/db.php`) n'est **jamais** committé — il vit hors docroot (`poesie-config-prive/db.php`).

## Déploiement

Transfert manuel via FTP (lftp) vers o2switch, identifiants dans `~/.config/o2switch/` (hors dépôt) :
- `site/*.php` → `poesie.serviceproi.fr/site/`
- `admin/*` (dont `.htaccess`) → `poesie.serviceproi.fr/admin/`
- `app/_db.php` → `poesie.serviceproi.fr/_db.php`
- `corpus/textes/*.txt` + `corpus/catalogue.json` → `poesie-corpus-prive/` (hors docroot)
- `config/db.php` (réel) / `.htpasswd` → `poesie-config-prive/` (hors docroot)

## État

Design V2 + administration éditoriale complète, vérifiés en production (voir rapports dans `~/Documents/poesie-mission-*.md`).

**2026-08-11 : comptes avec rôles pour l'admin.** Table `users` (`admin` = contrôle total, `lecteur` = lecture seule) créée en prod via `scripts/setup-utilisateurs.php` (script temporaire, exécuté une fois puis supprimé du serveur — conservé ici en référence comme les autres scripts de `scripts/`). Premier compte : `admin` / `ClefAdmin`, mot de passe modifiable depuis l'interface (`admin/mon-compte.php`). Ancienne authentification HTTP Basic (`.htaccess`) supprimée du serveur après vérification complète du nouveau système (login, blocage serveur du rôle lecteur sur chaque action, anti-verrouillage du dernier compte admin) — testé en local (MariaDB + serveur PHP intégré) puis re-vérifié en production.
