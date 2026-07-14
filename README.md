# Tu me connais ?

Jeu de couple à distance construit avec **Laravel 13**. L'un de vous crée un
salon, l'autre le rejoint avec un code, et c'est parti :

- **Quiz « Tu me connais ? »** — à chaque manche, une question porte sur
  l'un des deux : il répond la vérité pendant que l'autre devine sa réponse.
  Quand les deux ont répondu, les réponses se dévoilent et le joueur concerné
  décide si la devinette est bonne. Un point par bonne devinette, récap
  complet en fin de partie.
- **5 packs de questions** — Découverte, Fun, Romantique,
  Coquin (séduction et sous-entendus) et **Piment** pour pimenter la
  vie de couple (réservé aux adultes consentants). 6, 10 ou 14 questions
  par partie.
- **Accords automatiques** — chacun coche son sexe en entrant dans le
  jeu, et toutes les questions s'accordent correctement (fou/folle,
  fier/fière, séduisant/séduisante...). Syntaxe des modèles : `{name}`
  pour le prénom, `{fou|folle}` pour un accord avec la personne visée,
  `{p:beau|belle}` pour un accord avec son/sa partenaire.
- **Questions par IA (optionnel)** — avec une clé
  [OpenRouter](https://openrouter.ai), le jeu génère des questions inédites
  dans le style du pack choisi (modèles gratuits par défaut). Sans clé, ou si
  l'API ne répond pas, il bascule automatiquement sur les packs intégrés.
- **Question du jour** — une nouvelle question chaque jour ; chacun répond
  sans voir la réponse de l'autre, puis tout se dévoile. L'historique forme
  votre petit journal de couple.

Aucune inscription : un prénom, un code de salon, et le lien d'invitation à
envoyer à sa moitié. Interface mobile-first en français. Le « temps réel »
fonctionne par polling AJAX (une requête toutes les 2,5 s), donc **aucun
WebSocket ni Node.js n'est nécessaire : parfait pour un hébergement
mutualisé**.

## Prérequis

- PHP ≥ 8.3 avec les extensions `pdo_sqlite`, `mbstring`, `curl`, `openssl`
- Composer (uniquement pour installer les dépendances)
- Aucune base MySQL nécessaire : SQLite par défaut (un simple fichier)

## Installation locale

```bash
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate
php artisan serve
```

Le jeu est disponible sur http://127.0.0.1:8000.

## Activer les questions par IA (optionnel)

Dans `.env` :

```dotenv
OPENROUTER_API_KEY=sk-or-v1-votre-cle
OPENROUTER_MODEL=nvidia/nemotron-3-ultra-550b-a55b:free
```

**Attention :** la clé ne doit **jamais** être commitée : elle vit uniquement dans `.env`
(déjà ignoré par Git). Si votre clé a fuité quelque part, régénérez-la sur
https://openrouter.ai/keys.

## Déploiement sur un hébergement mutualisé

1. **Sur votre machine** : `composer install --no-dev --optimize-autoloader`,
   puis envoyez tout le projet (avec `vendor/`) par FTP/SFTP, par exemple
   dans `~/tumeconnais/`.
2. **Pointez le domaine vers `public/`** : la plupart des hébergeurs (OVH,
   o2switch, Hostinger...) permettent de choisir le dossier racine d'un
   domaine ou sous-domaine. Choisissez `tumeconnais/public`.
   - Si votre hébergeur ne le permet pas : placez le contenu de `public/`
     dans `www/` et ajustez les chemins `require` de `public/index.php`
     vers le dossier du projet.
3. **Créez `.env` sur le serveur** (copiez `.env.example`) et réglez :
   ```dotenv
   APP_ENV=production
   APP_DEBUG=false
   APP_URL=https://votre-domaine.fr
   APP_KEY=            # générez-la en local avec: php artisan key:generate --show
   DB_CONNECTION=sqlite
   OPENROUTER_API_KEY=sk-or-v1-...   # optionnel
   ```
4. **Créez la base** : un fichier vide `database/database.sqlite`, puis
   lancez les migrations. En SSH : `php artisan migrate --force`. Sans SSH :
   créez la base en local (`php artisan migrate`) et envoyez le fichier
   `database/database.sqlite` par FTP.
5. Vérifiez que `storage/` et `bootstrap/cache/` sont accessibles en
   écriture (chmod 755/775 selon l'hébergeur).

## Lancer les tests

```bash
php artisan test
```

## Architecture

| Élément | Rôle |
| --- | --- |
| `app/Http/Controllers/GameController.php` | Toute la logique de jeu (salons, manches, scores, question du jour) |
| `app/Models/` | `Room`, `Player`, `Round`, `DailyEntry` |
| `app/Support/Questions.php` | Packs de questions intégrés + question du jour |
| `app/Services/OpenRouter.php` | Génération de questions par IA (avec repli) |
| `resources/views/game.blade.php` | Page unique du jeu |
| `public/assets/` | CSS + JS vanilla (polling, icônes SVG inline, aucune dépendance front) |

L'API (préfixe `/api`) est authentifiée par un token de joueur généré à la
création/au join du salon et stocké dans le `localStorage` du téléphone :
fermer l'onglet ne fait pas perdre la partie.
# Fable5
