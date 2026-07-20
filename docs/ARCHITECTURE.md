# Architecture technique

## 1. Stack

| Couche | Choix | Rôle |
| --- | --- | --- |
| Backend | Laravel (PHP 8.4+) | logique métier, ORM, jobs, admin |
| Bridge | Inertia.js | pont SPA sans API REST séparée à maintenir |
| Frontend | React 19 + TypeScript | UI |
| Styles | Tailwind CSS v4 + shadcn/ui (Radix) | design system |
| State panier client | Zustand | store léger côté client (sync avec `carts`/`cart_items` serveur) |
| Base de données | PostgreSQL | relationnel, JSONB, robuste pour e-commerce |
| Cache / queues | Redis + Laravel Horizon | jobs asynchrones (emails, sync stock, avis) |
| Recherche | Laravel Scout + Meilisearch | recherche catalogue tolérante aux fautes/typos |
| Paiement | Stripe (Checkout / Payment Element) | CB, Apple Pay, Google Pay |
| Emails transactionnels | Resend (mail driver Laravel) | confirmation commande, expédition |
| Livraison | Sendcloud | tarifs, étiquettes, tracking |
| Médias | Cloudinary | upload/transformation/CDN images produit |
| Admin | Inertia + React (custom) | back-office sur le même stack que le storefront, pas de framework admin externe (pas de Livewire/Vue) |
| Rôles/permissions | Spatie Laravel-Permission | admin/staff/support |
| PDF | dompdf | factures |
| SEO | Artesaos/SEOTools (ou équivalent) | meta tags dynamiques |
| Tests | Pest | tests unitaires/feature |
| Analyse statique | Larastan (PHPStan) | qualité de code |
| Monitoring erreurs | Sentry | suivi des erreurs prod |

Le starter Laravel React (Fortify + passkeys + 2FA + shadcn/ui) déjà en place couvre l'auth de base ;
il reste à construire tout le domaine e-commerce par-dessus.

## 2. Pourquoi Inertia (pas d'API REST séparée)

Inertia permet de garder des contrôleurs Laravel classiques qui renvoient des props React, sans
construire/maintenir une API JSON séparée pour un front qui ne sera consommé que par ce site.
**Exception** : si un besoin d'API publique apparaît plus tard (app mobile, intégration
partenaire), prévoir des contrôleurs API dédiés sous `routes/api.php`, distincts des contrôleurs
Inertia — ne pas mélanger les deux dans les mêmes classes.

## 3. Architecture du projet

```text
Kbeauty/
├── app/                                # Backend Laravel
│   ├── Actions/                        # Logique métier isolée et réutilisable (Action pattern)
│   │   ├── Fortify/                    # Déjà présent (auth)
│   │   ├── Cart/
│   │   │   ├── SyncThresholdGifts.php  # Cadeaux à paliers (voir DATA_MODEL.md)
│   │   │   └── ApplyCoupon.php
│   │   ├── Orders/
│   │   │   ├── PlaceOrder.php
│   │   │   └── RefundOrder.php
│   │   └── Reviews/
│   │       └── SubmitReview.php
│   ├── Console/Commands/               # Déjà présent
│   ├── Enums/                          # OrderStatus, PaymentStatus, ReviewStatus... (PHP natifs)
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Settings/               # Déjà présent
│   │   │   ├── Storefront/             # Contrôleurs publics (catalogue, panier, checkout, compte)
│   │   │   │   ├── CatalogController.php
│   │   │   │   ├── ProductController.php
│   │   │   │   ├── CartController.php
│   │   │   │   ├── CheckoutController.php
│   │   │   │   └── AccountController.php
│   │   │   ├── Admin/                  # Contrôleurs back-office (staff/admin), Inertia comme le reste
│   │   │   │   ├── DashboardController.php
│   │   │   │   ├── ProductController.php
│   │   │   │   ├── OrderController.php
│   │   │   │   └── ReviewController.php
│   │   │   └── Webhooks/
│   │   │       └── StripeWebhookController.php
│   │   ├── Middleware/                 # Déjà présent
│   │   └── Requests/                   # Form Requests de validation
│   ├── Jobs/                           # Jobs Horizon (email, sync stock, indexation Scout)
│   ├── Models/                         # Product, ProductVariant, Order, Review... (voir DATA_MODEL.md)
│   ├── Notifications/                  # OrderConfirmed, OrderShipped...
│   ├── Observers/                      # ProductObserver (reviews_avg_rating), OrderObserver
│   ├── Providers/                      # Déjà présent
│   └── Services/                       # Intégrations externes
│       ├── StripeService.php
│       ├── SendcloudService.php
│       └── MeilisearchIndexer.php
├── database/
│   ├── factories/                      # Données de démo réalistes (catalogue K-beauty)
│   ├── migrations/                     # Une migration par entité (voir DATA_MODEL.md)
│   └── seeders/
├── resources/
│   ├── css/
│   ├── js/                             # Frontend Inertia + React
│   │   ├── actions/                    # Déjà présent (Wayfinder — appels typés vers les routes Laravel)
│   │   ├── components/
│   │   │   ├── ui/                     # Déjà présent (shadcn/ui)
│   │   │   ├── storefront/             # ProductCard, ProductGallery, VariantSelector,
│   │   │   │                           # ReviewList, GiftProgressBar, MegaMenu...
│   │   │   └── admin/                  # DataTable, AdminSidebar, AdminHeader... réutilisés par
│   │   │                               # toutes les pages admin (16.x)
│   │   ├── hooks/                      # Déjà présent
│   │   ├── layouts/                    # Déjà présent (app/auth/settings) + layouts/storefront à ajouter
│   │   ├── lib/                        # Déjà présent
│   │   ├── pages/                      # Composants de page Inertia (mapping direct avec les contrôleurs)
│   │   │   ├── auth/                   # Déjà présent — connexion/inscription (public, non connecté)
│   │   │   ├── settings/               # Déjà présent — réglages compte (connecté)
│   │   │   ├── storefront/             # À ajouter — PUBLIC, aucune auth requise
│   │   │   │   ├── Home.tsx
│   │   │   │   ├── Catalog.tsx
│   │   │   │   ├── Product.tsx
│   │   │   │   ├── Brand.tsx
│   │   │   │   ├── Cart.tsx
│   │   │   │   └── Checkout.tsx        # étapes checkout : invité autorisé jusqu'au paiement
│   │   │   ├── account/                # À ajouter — PROTÉGÉ (middleware `auth`), espace client connecté
│   │   │   │   ├── Dashboard.tsx
│   │   │   │   ├── Orders.tsx
│   │   │   │   ├── Addresses.tsx
│   │   │   │   └── Wishlist.tsx
│   │   │   └── admin/                  # À ajouter — PROTÉGÉ (middleware `auth` + rôle Spatie staff/admin)
│   │   │       ├── Dashboard.tsx
│   │   │       ├── products/
│   │   │       │   ├── Index.tsx       # DataTable réutilisable (composant table shadcn/ui)
│   │   │       │   ├── Create.tsx
│   │   │       │   └── Edit.tsx
│   │   │       ├── orders/
│   │   │       └── reviews/
│   │   ├── routes/                     # Déjà présent (Wayfinder)
│   │   ├── stores/                     # À ajouter
│   │   │   └── cart-store.ts           # Zustand — état panier optimiste côté client
│   │   ├── types/                      # Déjà présent
│   │   └── wayfinder/                  # Déjà présent
│   └── views/                          # Déjà présent (shell Blade minimal pour Inertia)
├── routes/
│   ├── web.php                         # Routes storefront (Inertia)
│   ├── admin.php                       # À ajouter (préfixe /admin, middleware auth + rôle Spatie)
│   ├── settings.php                    # Déjà présent
│   ├── webhooks.php                    # À ajouter (Stripe, hors CSRF/session)
│   └── console.php                     # Déjà présent
├── docs/                                # Ce dossier (PRD, STACK, FEATURES, DATA_MODEL...)
└── tests/                               # Pest — tests unitaires/feature du domaine e-commerce
```

Le pattern **Action** déjà utilisé par le starter (`app/Actions/Fortify`) doit être reproduit pour
toute la logique métier non triviale (paliers de cadeaux, avis, commande) — controllers minces,
logique testable en isolation avec Pest.

Convention de nommage des pages Inertia : un contrôleur `Storefront\ProductController::show()`
retourne `Inertia::render('storefront/Product', [...])`, qui correspond au fichier
`resources/js/pages/storefront/Product.tsx` — même arborescence des deux côtés pour s'y retrouver
facilement (déjà la convention utilisée par `auth/` et `settings/` dans le starter).

### Les 3 espaces du site, et où ils vivent réellement dans le code

| Espace | Accès | Techno | Où ça vit |
| --- | --- | --- | --- |
| **Public** (storefront) | tout le monde, sans compte | Inertia + React | `resources/js/pages/storefront/`, contrôleurs `app/Http/Controllers/Storefront/` |
| **Compte client** | connecté (`middleware('auth')`) | Inertia + React | `resources/js/pages/account/` + `settings/` (déjà présent), mêmes contrôleurs `Storefront/` (namespace conservé car ce sont toujours des pages "boutique", juste protégées) |
| **Admin** (back-office) | staff/admin (rôle Spatie) | Inertia + React, même stack que le storefront | `resources/js/pages/admin/`, contrôleurs `app/Http/Controllers/Admin/`, routes `routes/admin.php`, préfixe `/admin` |

**Point important** : contrairement à un choix comme Filament, l'admin **n'est pas** un sous-site à
part avec son propre système de rendu — c'est le même Inertia/React que le public et le compte
client, juste un troisième dossier de pages (`pages/admin/`) protégé par un middleware de rôle.
Un seul stack front dans tout le repo, donc les composants `components/ui` (shadcn/ui) et les
patterns (Form Request, Action) sont partagés entre storefront et admin. La contrepartie : chaque
écran CRUD (produits, commandes, avis...) doit être construit à la main (page + contrôleur + Form
Request), il n'y a pas de génération automatique façon Filament — d'où l'intérêt du composant
`DataTable` réutilisable (`components/admin/`) pour ne pas réécrire le tri/pagination à chaque
ressource. Le routing des permissions admin (qui voit quoi dans `/admin`) est géré par
`spatie/laravel-permission` via un middleware de rôle sur `routes/admin.php`, voir §8 Sécurité &
conformité.

Découpage des routes dans `routes/web.php` (schéma, pas le fichier final) :

```php
// Public — aucune auth
Route::get('/', [HomeController::class, 'index']);
Route::get('/c/{category}', [CatalogController::class, 'show']);
Route::get('/p/{product}', [ProductController::class, 'show']);
Route::get('/panier', [CartController::class, 'index']);
Route::get('/commande', [CheckoutController::class, 'index']);

// Compte client — protégé
Route::middleware('auth')->prefix('compte')->group(function () {
    Route::get('/', [AccountController::class, 'dashboard']);
    Route::get('/commandes', [AccountController::class, 'orders']);
    Route::get('/adresses', [AccountController::class, 'addresses']);
    Route::get('/favoris', [AccountController::class, 'wishlist']);
});

// Admin — protégé (auth + rôle Spatie staff/admin/support), routes/admin.php
Route::middleware(['auth', 'role:admin|staff|support'])->prefix('admin')->group(function () {
    Route::get('/', [Admin\DashboardController::class, 'index']);
    Route::resource('produits', Admin\ProductController::class);
    Route::get('/commandes', [Admin\OrderController::class, 'index']);
    Route::get('/avis', [Admin\ReviewController::class, 'index']);
});
```

- Le store Zustand gère l'état **optimiste** du panier (UI instantanée), mais la source de vérité
  reste toujours le panier serveur (`carts`/`cart_items`) — resynchronisation après chaque réponse
  Inertia pour éviter toute divergence, en particulier sur les cadeaux à paliers et les prix.
- Composants shadcn/ui existants réutilisés au maximum ; nouveaux composants storefront ajoutés à
  côté sans dupliquer ce qui existe déjà dans `components/ui`.

## 4. Flux de paiement (Stripe)

1. Le client valide le récapitulatif → `POST /checkout` (contrôleur Inertia) crée l'`Order`
   en statut `pending` + un `PaymentIntent` Stripe (Payment Element : CB, Apple Pay, Google Pay).
2. Le paiement est confirmé côté client (Stripe.js), puis **confirmé côté serveur uniquement via
   webhook Stripe** (`payment_intent.succeeded`) — jamais faire confiance au retour navigateur seul.
3. Le webhook met à jour `Order.status = paid`, `Payment.status = succeeded`, déclenche un Job
   Horizon (email confirmation via Resend, décrément stock, création `Shipment` à traiter).
4. Les remboursements passent par l'admin (Inertia/React) → Action `RefundOrder` → API Stripe → `Refund`.

## 5. Recherche (Scout + Meilisearch)

- Indexer uniquement les produits `status = published`.
- Ré-indexation automatique via les événements Eloquent (`Searchable` trait) + Job en file Horizon
  pour ne pas bloquer les requêtes admin lors d'un import en masse.
- **Recommandation** : ne pas bloquer le MVP là-dessus. Une recherche SQL basique
  (`ILIKE` PostgreSQL + index trigram `pg_trgm`) suffit pour un catalogue de départ (quelques
  centaines de produits) ; Meilisearch devient rentable au-delà, ou si la faute de frappe /
  recherche par ingrédient doit être tolérante.

## 6. Média (Cloudinary)

- Upload direct depuis l'admin (Inertia/React) et éventuellement depuis un futur espace vendeur.
- Stocker uniquement le `public_id` Cloudinary en base, générer les URLs transformées
  (formats/tailles responsive) à la volée côté frontend plutôt que dupliquer plusieurs fichiers.

## 7. Environnements & déploiement

- `.env` déjà présent — séparer clairement dev/staging/prod (clés Stripe test vs live, Meilisearch
  key, Cloudinary, Resend, Sentry DSN).
- Queues : Horizon doit tourner en process dédié (pas `sync` driver) dès que les emails/jobs sont
  en place, y compris en dev via `composer dev` (déjà scripté pour `queue:listen`).
- CI : réutiliser les scripts déjà présents (`composer ci:check`, `pint`, `phpstan`, `pest`) et
  ajouter les tests Pest du domaine e-commerce au fur et à mesure.

## 8. Sécurité & conformité

- Validation serveur systématique des prix/totaux au moment du paiement (ne jamais faire confiance
  au panier client, cf. `DATA_MODEL.md`).
- Webhooks Stripe vérifiés par signature (`Stripe-Signature` header).
- RGPD : cookies de mesure/marketing chargés uniquement après consentement (bandeau cookies avant
  tout pixel Meta/TikTok/Google Ads).
- Permissions de l'admin strictement scopées via Spatie, vérifiées à la fois dans le middleware de
  route (`role:...`) et dans chaque contrôleur/policy (un `support` ne doit pas pouvoir modifier
  les prix catalogue, un `staff` ne doit pas voir les remboursements sans permission dédiée).
