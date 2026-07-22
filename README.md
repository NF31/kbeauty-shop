# K-Beauty — Site E-commerce Cosmétique Coréenne

Site e-commerce sur-mesure de cosmétiques coréens (K-Beauty), au positionnement premium/épuré,
inspiré de [uniikon.com](https://uniikon.com).

## Stack

Laravel 13 + Inertia.js + React 19 + TypeScript, Tailwind CSS, PostgreSQL, admin Inertia/React
custom (pas de Filament/Livewire), Stripe, Spatie Permission, Cloudinary, Resend, Redis/Horizon,
Meilisearch. Détail complet et justification des choix dans [`docs/STACK.md`](docs/STACK.md).

Le projet part du [starter officiel Laravel React](https://github.com/laravel/react-starter-kit)
(auth Fortify, passkeys, 2FA, shadcn/ui déjà scaffoldés) — le domaine e-commerce est construit
par-dessus.

## Documentation

Toute la documentation projet vit dans [`docs/`](docs) :

| Document | Contenu |
| --- | --- |
| [`PRD.md`](docs/PRD.md) | Vision produit, objectifs, personas, périmètre |
| [`STACK.md`](docs/STACK.md) | Stack technique, packages à installer, config `.env` |
| [`FEATURES.md`](docs/FEATURES.md) | Backlog fonctionnel phasé (P0-P3) avec package requis par feature |
| [`DATA_MODEL.md`](docs/DATA_MODEL.md) | Modèle de données PostgreSQL (catalogue, panier, commandes, avis...) |
| [`ARCHITECTURE.md`](docs/ARCHITECTURE.md) | Organisation du code, arborescence du projet, flux Stripe, sécurité |
| [`DESIGN_GUIDE.md`](docs/DESIGN_GUIDE.md) | Charte graphique et composants UI |
| [`CONTENT_STRUCTURE.md`](docs/CONTENT_STRUCTURE.md) | Arborescence du site, structure des pages, pages légales |
| [`ROADMAP.md`](docs/ROADMAP.md) | Jalons de livraison et séquencement |

## Démarrage

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate
composer dev
```

`composer dev` lance en parallèle le serveur Laravel, le worker de queue et Vite (voir
`composer.json`).

## Qualité

```bash
composer lint          # Pint
composer types:check   # PHPStan / Larastan
composer test           # Pint + PHPStan + Pest
npm run lint            # ESLint
npm run types:check     # tsc
```
