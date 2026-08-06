---
title: Stack technique
order: 6
---

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
| Panier | `CartService` natif (`bumbummen99/shoppingcart` évalué puis abandonné/incompatible Laravel 13, voir §4) |
| Multi-langue | `laravel-react-i18n` (front) + `spatie/laravel-translatable` (colonnes traduisibles), voir `ARCHITECTURE.md` §3ter |
| Recherche | Laravel Scout + Meilisearch |
| Médias | Cloudinary (SDK officiel `cloudinary/cloudinary_php` — `cloudinary-labs/cloudinary-laravel` abandonné, incompatible Laravel 13) |
| Emails | Resend (via `resend-laravel`) |
| Queues | Laravel Horizon + Redis |
| Admin panel | Custom (Inertia + React — même stack que le storefront, pas de framework admin externe) |
| Livraison | Sendcloud (API REST) |
| Validation frontend | Zod + React Hook Form |
| State panier | Zustand |

## 2. Packages Composer (PHP)

Tous les packages ci-dessous sont installés (voir `composer.json` pour les contraintes de version
exactes) :

```
spatie/laravel-permission      stripe/stripe-php               brick/money
cloudinary/cloudinary_php      laravel/scout                    meilisearch/meilisearch-php
resend/resend-laravel          spatie/laravel-sluggable         spatie/laravel-sitemap
spatie/laravel-translatable    barryvdh/laravel-dompdf          laravel/horizon
sentry/sentry-laravel          spatie/laravel-health            predis/predis
spatie/laravel-activitylog     spatie/laravel-backup            spatie/laravel-honeypot
laravel/pulse                  spatie/laravel-csp               spatie/laravel-webhook-client
petebishwhip/laradocs          laravel/chisel                   laravel/passkeys
```

> `artesaos/seotools` évalué puis écarté : SEO géré à la main (meta tags + JSON-LD dans
> `seo-head.tsx`, voir `ARCHITECTURE.md`).

### Dev only (Composer)

Installés : `laravel/telescope`, `barryvdh/laravel-debugbar`, `andreapollastri/checkpoint`
(scanner de sécurité, `php artisan checkpoint:scan`).

> Déjà présents dans le starter, ne pas réinstaller : `laravel/fortify`, `inertiajs/inertia-laravel`,
> `laravel/framework`, `laravel/tinker`, `laravel/wayfinder`, `larastan/larastan`, `pestphp/pest`
> + `pest-plugin-laravel`, `fakerphp/faker`, `laravel/pail`, `laravel/pint`, `laravel/sail`,
> `mockery/mockery`, `nunomaduro/collision`, `laravel/pao`.

### Packages optionnels (Phase 11, à installer seulement si la feature est activée)

```bash
composer require laravel/cashier           # réachat/abonnement récurrent (P3, Stripe Billing)
```

## 3. Packages NPM (JS/TS)

Tous les packages ci-dessous sont installés (voir `package.json`) : `zustand`, `zod`,
`react-hook-form`, `@radix-ui/react-tabs`, `laravel-react-i18n`, `recharts`, `heroicons` (set
d'icônes consommé au runtime par Laradocs, voir §2 de `FEATURES.md` / documentation interne).

> L'admin (back-office) est un module Inertia/React comme le storefront, pas un package séparé.
> Pas de nouvelle dépendance de table de données pour l'instant : un composant `DataTable`
> réutilisable est construit sur le composant `Table` de shadcn/ui (déjà dans le starter). Si le
> besoin de tri/filtrage/pagination avancé le justifie, évaluer `@tanstack/react-table` à ce
> moment-là plutôt que par anticipation.
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
| `CartService` (natif, `app/Services/CartService.php`) | `bumbummen99/shoppingcart` abandonné/incompatible Laravel 13, `darryldecode/cart` et `binafy/laravel-cart` écartés aussi (voir `FEATURES.md` 8.1) — panier serveur maison sur les tables `carts`/`cart_items` |
| `brick/money` | calculs monétaires précis (évite les erreurs d'arrondi float), complète le panier et les totaux de commande |
| `laravel/scout` + `meilisearch/meilisearch-php` | recherche catalogue tolérante aux fautes de frappe — voir note de séquencement dans `ARCHITECTURE.md` (pas indispensable dès le tout premier lancement) |
| `spatie/laravel-sluggable` | slugs SEO auto-générés pour produits/catégories/articles |
| `spatie/laravel-sitemap` + `artesaos/seotools` | SEO technique (sitemap.xml, meta tags dynamiques) |
| `laravel/horizon` + Redis | supervision des queues (emails, indexation Scout, jobs Sendcloud) |
| `sentry/sentry-laravel` | remontée d'erreurs prod, indispensable dès la mise en ligne réelle |
| `spatie/laravel-health` | vue d'ensemble santé infra (DB, Redis, queue, Horizon, disque, sauvegardes) sur `/admin/health` (22.5) — complète Sentry (erreurs applicatives) par une vision infra que Sentry ne couvre pas |
| `laravel/telescope` / `barryvdh/laravel-debugbar` | debug local uniquement — **ne jamais activer en production** |
| `laravel-react-i18n` + `spatie/laravel-translatable` | multi-langue FR/EN : `t()`/`tChoice()` côté front (clés = phrase française) + colonnes JSON traduisibles côté back (`Product.name`/`description`/etc.) — voir `ARCHITECTURE.md` §3ter |
| `laravel/pulse` | dashboard de perf temps réel (`/pulse`, requêtes lentes, jobs, exceptions), gaté `permission:settings.manage` |
| `spatie/laravel-csp` | Content-Security-Policy en config plutôt qu'en middleware maison, actif en mode report-only |
| `spatie/laravel-webhook-client` | modèle `WebhookCall` + `ProcessStripeWebhookJob` (queue, retry, dédup par `event.id`) en couche fine devant `StripeWebhookController`, voir `ARCHITECTURE.md` |
| `andreapollastri/checkpoint` (dev) | scanner de sécurité statique (CVE, injection, CSRF, mass-assignment...), `php artisan checkpoint:scan` |
| `petebishwhip/laradocs` | rend `docs/` consultable sur `/docs` (nav, recherche, SEO), gaté `permission:settings.manage` — documentation manuelle, pas de génération à partir du code |

## 5. Configuration `.env` (extraits clés)

### Mail (Resend)

```env
MAIL_MAILER=resend
RESEND_API_KEY=re_xxxxxxxxxxxx
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
SENTRY_TRACES_SAMPLE_RATE=1.0
VITE_SENTRY_DSN=
```

> Même DSN backend/frontend (un projet Sentry, environnements distingués par le tag `environment`,
> auto-dérivé d'`APP_ENV`/`import.meta.env.MODE`) — voir `FEATURES.md` 22.1.
>
> Ne jamais committer les vraies valeurs — garder `.env.example` à jour avec les clés vides à
> chaque nouveau package ajouté, comme c'est déjà fait pour le reste du projet.

## 6. Ordre d'installation recommandé

Aligné sur le phasage de `FEATURES.md` — pas besoin d'installer tout le stack dès le jour 1 :

1. **Fondations** : PostgreSQL, Fortify (déjà là), Spatie Permission.
2. **Catalogue** : Cloudinary, Spatie Sluggable.
3. **Panier/commande** : `CartService` natif (voir §4), `brick/money`, Stripe.
4. **Emails/queues** : Resend, Horizon + Redis.
5. **Avis/admin** : dompdf (le back-office admin est un module Inertia/React custom — même stack
   que le storefront, aucun package admin supplémentaire requis).
6. **Livraison** : Sendcloud.
7. **SEO/marketing** : Scout + Meilisearch, laravel-sitemap, SEOTools.
8. **Observabilité** : Sentry, Telescope/Debugbar (dev).

Voir `ROADMAP.md` pour le phasage complet du projet.
