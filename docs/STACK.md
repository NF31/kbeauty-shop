# Stack technique — Site E-commerce Laravel/Inertia

Référence unique des technologies et packages retenus pour le projet. `ARCHITECTURE.md` explique
le *pourquoi* de certains choix ; ce document liste précisément *quoi installer*.

## 1. Vue d'ensemble

| Catégorie | Technologie |
| --- | --- |
| Framework | Laravel 13 |
| Frontend | Inertia.js + React + TypeScript |
| Style | Tailwind CSS |
| Base de données | PostgreSQL |
| Auth | Laravel Fortify |
| Permissions | Spatie Laravel Permission |
| Paiement | Stripe (`stripe-php`) |
| Panier | `bumbummen99/shoppingcart` |
| Recherche | Laravel Scout + Meilisearch |
| Médias | Cloudinary Laravel |
| Emails | Resend (via `resend-laravel`) |
| Queues | Laravel Horizon + Redis |
| Admin panel | Filament |
| Livraison | Sendcloud (API REST) |
| Validation frontend | Zod + React Hook Form |
| State panier | Zustand |

## 2. Packages Composer (PHP)

Packages restant à installer (le reste du tableau §1 est déjà couvert par le starter, voir note
ci-dessous) :

```bash
composer require spatie/laravel-permission
composer require stripe/stripe-php
composer require bumbummen99/shoppingcart
composer require brick/money
composer require cloudinary-labs/cloudinary-laravel
composer require laravel/scout
composer require meilisearch/meilisearch-php
composer require resend/resend-laravel
composer require spatie/laravel-sluggable
composer require spatie/laravel-sitemap
composer require artesaos/seotools
composer require barryvdh/laravel-dompdf
composer require laravel/horizon
composer require filament/filament
composer require sentry/sentry-laravel
```

### Dev only (Composer)

```bash
composer require --dev laravel/telescope
composer require --dev barryvdh/laravel-debugbar
```

> Déjà présents dans le starter, ne pas réinstaller : `laravel/fortify`, `inertiajs/inertia-laravel`,
> `laravel/framework`, `laravel/tinker`, `laravel/wayfinder`, `laravel/chisel`, `laravel/passkeys`,
> `larastan/larastan`, `pestphp/pest` + `pest-plugin-laravel`, `fakerphp/faker`, `laravel/pail`,
> `laravel/pint`, `laravel/sail`, `mockery/mockery`, `nunomaduro/collision`.

### Packages optionnels (Phase 11, à installer seulement si la feature est activée)

```bash
composer require laravel-lang/lang         # multi-langue FR/EN — à évaluer, plusieurs options existent
composer require laravel/cashier           # réachat/abonnement récurrent (P3, Stripe Billing)
```

## 3. Packages NPM (JS/TS)

Packages restant à installer :

```bash
npm install zustand zod react-hook-form
npm install @radix-ui/react-tabs
```

> `@radix-ui/react-tabs` n'est pas déjà dans le starter (les autres `@radix-ui/react-*` oui) —
> nécessaire dès la Phase 2 pour les onglets Bénéfices/Description/Ingrédients/Avis de la fiche
> produit.
>
> `recharts` n'est nécessaire qu'à la Phase 7 (KPIs admin, voir `FEATURES.md`) — à installer à ce
> moment-là plutôt que dès le départ : `npm install recharts`.
>
> `@headlessui/react` n'est pas ajouté par défaut : les primitives `@radix-ui/react-*` déjà
> installées (base de shadcn/ui) couvrent la même fonction. Ne l'installer que si un composant
> précis manque vraiment côté Radix, pour éviter deux librairies de primitives UI en parallèle.

### Dev only (NPM)

Rien à ajouter — `eslint`, `prettier` et `typescript-eslint` (package combiné qui remplace
`@typescript-eslint/parser` + `@typescript-eslint/eslint-plugin`) sont déjà configurés dans le
starter.

> Déjà présents dans le starter, ne pas réinstaller : `@inertiajs/react`, `react`, `react-dom`,
> `typescript`, `tailwindcss`, `lucide-react`, `eslint`, `prettier`, `typescript-eslint`, ainsi que
> tous les `@radix-ui/react-*`.

## 4. Rôle de chaque brique non triviale

| Package | Pourquoi celui-ci plutôt qu'un autre |
| --- | --- |
| `bumbummen99/shoppingcart` | panier serveur prêt à l'emploi (successeur maintenu de `gloudemans/shoppingcart`), évite de réinventer la gestion panier/session |
| `brick/money` | calculs monétaires précis (évite les erreurs d'arrondi float), complète le panier et les totaux de commande |
| `laravel/scout` + `meilisearch/meilisearch-php` | recherche catalogue tolérante aux fautes de frappe — voir note de séquencement dans `ARCHITECTURE.md` (pas indispensable dès le tout premier lancement) |
| `spatie/laravel-sluggable` | slugs SEO auto-générés pour produits/catégories/articles |
| `spatie/laravel-sitemap` + `artesaos/seotools` | SEO technique (sitemap.xml, meta tags dynamiques) |
| `filament/filament` | back-office admin complet sans construire un CRUD Inertia maison pour chaque entité |
| `laravel/horizon` + Redis | supervision des queues (emails, indexation Scout, jobs Sendcloud) |
| `sentry/sentry-laravel` | remontée d'erreurs prod, indispensable dès la mise en ligne réelle |
| `laravel/telescope` / `barryvdh/laravel-debugbar` | debug local uniquement — **ne jamais activer en production** |

## 5. Configuration `.env` (extraits clés)

### Mail (Resend)

```env
MAIL_MAILER=resend
RESEND_KEY=re_xxxxxxxxxxxx
```

### Base de données (PostgreSQL)

```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=kbeauty
DB_USERNAME=postgres
DB_PASSWORD=
```

### Queues / Redis

```env
QUEUE_CONNECTION=redis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
```

### Stripe

```env
STRIPE_KEY=pk_test_xxx
STRIPE_SECRET=sk_test_xxx
STRIPE_WEBHOOK_SECRET=whsec_xxx
```

### Meilisearch

```env
SCOUT_DRIVER=meilisearch
MEILISEARCH_HOST=http://127.0.0.1:7700
MEILISEARCH_KEY=
```

### Cloudinary

```env
CLOUDINARY_URL=cloudinary://<api_key>:<api_secret>@<cloud_name>
```

### Sendcloud

```env
SENDCLOUD_PUBLIC_KEY=
SENDCLOUD_SECRET_KEY=
```

### Sentry

```env
SENTRY_LARAVEL_DSN=
```

> Ne jamais committer les vraies valeurs — garder `.env.example` à jour avec les clés vides à
> chaque nouveau package ajouté, comme c'est déjà fait pour le reste du projet.

## 6. Ordre d'installation recommandé

Aligné sur le phasage de `FEATURES.md` — pas besoin d'installer tout le stack dès le jour 1 :

1. **Fondations** : PostgreSQL, Fortify (déjà là), Spatie Permission.
2. **Catalogue** : Cloudinary, Spatie Sluggable.
3. **Panier/commande** : `bumbummen99/shoppingcart`, `brick/money`, Stripe.
4. **Emails/queues** : Resend, Horizon + Redis.
5. **Avis/admin** : Filament, dompdf.
6. **Livraison** : Sendcloud.
7. **SEO/marketing** : Scout + Meilisearch, laravel-sitemap, SEOTools.
8. **Observabilité** : Sentry, Telescope/Debugbar (dev).

Voir `ROADMAP.md` pour le phasage complet du projet.
