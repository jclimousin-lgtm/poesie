# Corpus de poésie — poesie.serviceproi.fr

Site de diffusion d'un corpus de textes (poésie, chansons, satire) hébergé sur o2switch.

## Architecture

- **SOURCE** — Google Drive (jamais modifié, jamais dans ce dépôt).
- **CATALOGUE** — base MySQL `nare8592_poesie`, table `documents` + `categories`/`series` (métadonnées et référencement).
- **CORPUS DE TRAVAIL** — `corpus/textes/INV-XXXX.txt` (116 fichiers) + `corpus/catalogue.json`, déployés sur le serveur hors docroot (`poesie-corpus-prive/`), jamais publics directement.
- **APPLICATION** :
  - `app/` — catalogue de travail interne (Mission 2) : `index.php`, `fiche.php`, `_db.php`.
  - `site/` — site public de diffusion (Mission « préparation technique ») : `accueil.php`, `liste.php`, `categorie.php`, `series.php`, `fiche.php`, `_layout.php`.
- `scripts/` — scripts ponctuels de mise en place du schéma (déjà exécutés en production, conservés comme référence/rejouable si besoin de reconstituer la base).
- `config/db.php.example` — modèle de configuration DB. Le fichier réel (`config/db.php`, avec le vrai mot de passe) n'est **jamais** committé — il vit uniquement sur le serveur, hors docroot (`poesie-config-prive/db.php`).

## Déploiement

Transfert manuel via FTP (lftp) vers o2switch, identifiants dans `~/.config/o2switch/` (hors dépôt) :
- `site/*.php` → `poesie.serviceproi.fr/site/`
- `app/*.php` → `poesie.serviceproi.fr/`
- `corpus/textes/*.txt` + `corpus/catalogue.json` → `poesie-corpus-prive/` (hors docroot)
- `config/db.php` (réel) → `poesie-config-prive/db.php` (hors docroot)

## État

Site V1 vérifié techniquement (accueil, liste, recherche, 9 catégories dont Poésie/À découvrir, séries, fiches, navigation) — voir rapports dans `~/Documents/poesie-mission-*.md`.
