# Liste des fonctionnalités — Site E-commerce Laravel/Inertia

Légende statut : 🟢 Prêt | 🟡 En cours | ⚪ À faire | 🔴 Bloqué
Priorité : **P0** = critique (MVP) | **P1** = important (V1.1) | **P2** = secondaire (V2+) | **P3** = long terme
Colonne **Dev** : **Backend** = Laravel/PHP uniquement | **Front** = React/Inertia uniquement | **Les deux**.
Numérotation `#.#` = section.tâche, continue sur tout le document (sert de référence stable dans les
commits/PR, ex. `feat(2.3): carrousel images produit`).

Stack cible : Laravel + Inertia + React + TypeScript, PostgreSQL, Tailwind CSS, Spatie
Permissions, admin Inertia/React custom (pas de Filament/Livewire), Stripe, Sendcloud, Resend, Redis/Horizon, Meilisearch (Scout),
Cloudinary, Zustand, Pest, Larastan, Sentry. Détail des choix et justification dans `ARCHITECTURE.md`,
schéma complet des tables dans `DATA_MODEL.md`, commandes d'installation dans `STACK.md`.

---

## Modèles de données & Migrations (vue d'ensemble)

Récapitulatif de toutes les tables de `DATA_MODEL.md`, pour vérifier d'un coup d'œil qu'aucune
n'est oubliée. Chaque table est réellement créée dans le cadre d'une tâche fonctionnelle
ci-dessous (pas de doublon de travail entre cette liste et les tâches numérotées) — la colonne
**Tâche** renvoie vers le `#.#` qui la migre.

| Table | Tâche | Statut |
| --- | --- | --- |
| `users` | 1.1/2.1 (starter) | 🟢 |
| `passkeys` | 2.1 (starter) | 🟢 |
| `roles` / `permissions` / `model_has_roles` / `model_has_permissions` / `role_has_permissions` | 2.2 | 🟢 |
| `brands` | 4.2 | 🟢 |
| `categories` | 4.2 | 🟢 |
| `product_category` (pivot) | 4.2 | 🟢 |
| `products` | 4.1 | 🟢 |
| `product_options` | 4.2 | 🟢 |
| `product_option_values` | 4.2 | 🟢 |
| `product_variants` | 4.2 | 🟢 |
| `variant_option_values` (pivot) | 4.2 | 🟢 |
| `product_images` | 5.1 | ⚪ |
| `inventory_movements` | 4.4 | 🟢 |
| `carts` | 8.1 | ⚪ |
| `cart_items` | 8.1 | ⚪ |
| `cart_gift_items` | 10.2 | ⚪ |
| `gift_threshold_rules` | 10.2 | ⚪ |
| `gift_threshold_rule_rewards` | 10.2 | ⚪ |
| `addresses` | 9.1 | ⚪ |
| `orders` | 9.2 | ⚪ |
| `order_items` | 9.2 | ⚪ |
| `payments` | 9.2 | ⚪ |
| `refunds` | 16.3 | ⚪ |
| `shipments` | 11.2 | ⚪ |
| `coupons` | 10.1 | ⚪ |
| `reviews` | 14.1 | ⚪ |
| `review_photos` | 15.3 | ⚪ |
| `articles` | 18.1 | ⚪ |
| `article_product` (pivot) | 18.2 | ⚪ |
| `newsletter_subscribers` | 13.2 | ⚪ |
| `wishlists` | 26.3 | ⚪ |

---

## PHASE 1 — Fondations

### 1. Setup & Configuration

| # | Tâche | Dev | Priorité | Statut | Détails |
| --- | --- | --- | --- | --- | --- |
| 1.1 | Setup Laravel + starter kit Inertia/React | Les deux | P0 | 🟢 | Starter en place (Fortify + passkeys + 2FA + shadcn/ui), Laravel 13.20.0. `composer run dev` lance bien `php artisan serve` + `queue:listen` + `npm run dev` (Vite) en parallèle via `concurrently`, `.env` déjà généré. |
| 1.2 | Setup PostgreSQL + migrations de base | Backend | P0 | 🟢 | `DB_CONNECTION=pgsql` en local (Neon, connexion directe via `DB_URL`, **jamais** l'endpoint `-pooler`). `.env.example` mis à jour. Migrations de base passées avec succès. Bug concrètement reproduit avec l'endpoint `-pooler` (mode transaction PgBouncer) : `migrate:rollback` a répondu "DONE" sans réellement dropper la colonne, provoquant un `Duplicate column` au remigrate — état corrigé manuellement. Confirme l'avertissement : connexion directe uniquement. |
| 1.3 | Setup Tailwind CSS | Front | P0 | 🟢 | Tailwind v4 (`@tailwindcss/vite`) + `tw-animate-css` + shadcn/ui/Radix confirmés dans `package.json`. `resources/css/app.css` correctement configuré (thème oklch, `@source` sur les vues). `npm run build` compile sans erreur, CSS généré dans `public/build/`. |
| 1.4 | Validation env variables | Backend | P1 | 🟢 | `php artisan config:cache` / `config:clear` vérifiés en local, aucune clé manquante. `.env.example` à jour pour les packages déjà installés (voir 1.2). Les clés Stripe/Cloudinary/Meilisearch/Sendcloud/Sentry/Resend seront ajoutées à `.env.example` au fur et à mesure de leur installation (Phases 2+), pas avant — voir `STACK.md` §5. |

### 2. Auth & permissions

| # | Tâche | Dev | Priorité | Statut | Détails |
| --- | --- | --- | --- | --- | --- |
| 2.1 | Auth (Fortify) — login/register/reset password | Les deux | P0 | 🟢 | `FortifyServiceProvider::configureViews()` OK, toutes les routes (login/register/forgot-password/reset-password/2FA/confirm-password) enregistrées et testées : pages Inertia répondent 200, création d'un `User` via factory + hash de mot de passe validés en base PostgreSQL. Envoi réel des emails de reset à revérifier une fois Resend branché (5.1) — actuellement `MAIL_MAILER=log`. |
| 2.2 | Rôles & permissions (Spatie) — admin/staff/support/customer | Backend | P0 | 🟢 | `spatie/laravel-permission` v8.3 installé, migration `create_permission_tables` exécutée, trait `HasRoles` ajouté à `User`. `RolePermissionSeeder` crée les 6 permissions (`products.manage`, `orders.manage`, `orders.refund`, `reviews.moderate`, `content.manage`, `settings.manage`) et les rôles `admin` (toutes), `staff` (produits/commandes), `support` (commandes/avis, pas catalogue ni finance) — `customer` reste implicite (pas de rôle Spatie). Assignation de rôle + vérification de permission testées bout en bout via `tests/Feature/RolePermissionTest.php` (5 tests Pest), suite complète (44 tests) toujours verte. |

### 3. Layout général

| # | Tâche | Dev | Priorité | Statut | Détails |
| --- | --- | --- | --- | --- | --- |
| 3.1 | Layout général storefront (Navbar, Footer) | Front | P1 | 🟢 | `resources/js/layouts/storefront/` (template) + `layouts/storefront-layout.tsx` (wrapper, même pattern que `app-layout.tsx`). `StorefrontHeader`/`StorefrontFooter` dans `components/storefront/`. Header alimenté par une liste `categoryNavItems: NavItem[]` vide pour l'instant — le mega-menu (24.3) n'aura qu'à peupler cette liste, pas à restructurer le header. Avatar/dropdown si connecté, sinon boutons connexion/inscription. `tsc`, `eslint` et `vite build` passent sans erreur ; pas encore de page consommatrice (catalogue = Phase 2). |

---

## PHASE 2 — Catalogue produits

### 4. Modèles de données catalogue

| # | Tâche | Dev | Priorité | Statut | Détails |
| --- | --- | --- | --- | --- | --- |
| 4.1 | Modèle Product (nom, description) | Backend | P0 | 🟢 | Table `products` migrée avec le schéma complet de `DATA_MODEL.md` (`brand_id` sans contrainte FK pour l'instant — ajoutée en 4.2 une fois `brands` créée). `status` en enum PHP natif `App\Enums\ProductStatus` (`draft`/`published`/`archived`), `SoftDeletes` — un produit retiré du catalogue ne doit jamais casser l'historique des `order_items` (snapshot du nom/prix). **Prix et stock vivent sur `product_variants` (4.2), pas sur `products`** — le titre de cette tâche est trompeur sur ce point, corrigé ici. Modèle + factory + 5 tests Pest (statut par défaut, publication, cast `skin_types`, soft delete, unicité du slug), Larastan clean. |
| 4.2 | Modèle Brand / Category / Variant (axes multiples) | Backend | P0 | 🟢 | `brands` (marque, `country_of_origin`) + FK `products.brand_id` ajoutée (différée depuis 4.1). `categories` en arbre (`parent_id` auto-référencé, relations `parent()`/`children()`), pivot `product_category` (many-to-many explicite, nom de table non conventionnel donc précisé dans `belongsToMany()` des deux côtés). Variantes modélisées en axes (`product_options` + `product_option_values` + pivot `variant_option_values`) pour supporter contenance **et** teinte sans migration supplémentaire — pas une simple colonne `label`. 5 modèles + 5 factories + 6 tests Pest (relation brand, arbre catégories, many-to-many produit/catégories, variante mono-axe, variante bi-axes, unicité SKU), Larastan clean. |
| 4.3 | Liste INCI ingrédients par produit | Les deux | P0 | 🟢 | Colonne `ingredients_inci` (text) sur `products` — déjà migrée en 4.1, donc pas de nouvelle colonne ici. Le vrai travail restant : `App\Observers\ProductObserver` (attaché via `#[ObservedBy]`) bloque toute sauvegarde d'un produit `status=published` sans `ingredients_inci` renseigné (`App\Exceptions\MissingInciListException`), y compris sur une mise à jour qui viderait le champ d'un produit déjà publié — obligation légale cosmétique appliquée au niveau modèle, donc valable pour tout point d'entrée (admin, seeders, API future), pas juste un formulaire. 5 tests Pest. Affichage front dans l'onglet dédié de la fiche produit reste à faire en 6.2. |
| 4.4 | Gestion stock (décrémentation auto) | Backend | P0 | 🟢 | Table `inventory_movements` (`product_variant_id`, `type` enum `App\Enums\InventoryMovementType`, `quantity` **signé** — positif réassort/retour, négatif vente —, `note`, `created_at` seul : ledger immuable, pas d'`updated_at`). `App\Services\StockService::recordMovement()` applique le delta à `product_variants.stock_quantity` dans une transaction avec `lockForUpdate()` (verrou pessimiste anti-survente en cas de ventes concurrentes) et lève `App\Exceptions\InsufficientStockException` si le stock deviendrait négatif — rollback automatique, aucun mouvement n'est enregistré. Le déclenchement réel (appel de `recordMovement()` avec `Sale`) se fera depuis le webhook Stripe (9.4), jamais au moment du `PaymentIntent` créé — ce mécanisme est le service générique, pas encore branché à un point d'entrée HTTP. 4 tests Pest (réassort, vente, survente refusée + stock inchangé, historique des mouvements). |
| 4.5 | Alertes stock bas | Backend | P2 | ⚪ | Notification Laravel native (pas de package) déclenchée par un seuil configurable sur `product_variants.stock_quantity`, envoyée à l'admin via mail. Canal `database` natif Laravel prévu aussi, pour un affichage futur dans le dashboard admin (16.1) — pas de canal Filament, l'admin étant Inertia/React. |

### 5. Médias produits

| # | Tâche | Dev | Priorité | Statut | Détails |
| --- | --- | --- | --- | --- | --- |
| 5.1 | Upload images produits (Cloudinary) | Les deux | P0 | ⚪ | `cloudinary-labs/cloudinary-laravel`. Ne stocker que le `public_id` en base (`product_images.path`), générer les URLs transformées à la volée côté front plutôt que dupliquer des fichiers. Upload fait depuis l'admin (16.2), page React dédiée. |
| 5.2 | Carrousel images produit | Front | P0 | ⚪ | Composant React custom dans `resources/js/components/storefront/ProductGallery`. Consomme `product_images` triées par `position`, avec `product_variant_id` nullable pour afficher une image spécifique à la variante sélectionnée. |

### 6. Pages publiques catalogue

| # | Tâche | Dev | Priorité | Statut | Détails |
| --- | --- | --- | --- | --- | --- |
| 6.1 | Page liste produits (catalogue public) | Les deux | P0 | ⚪ | `Storefront\CatalogController` → `Inertia::render('storefront/Catalog')`. Prévoir la pagination et le point d'entrée des filtres (7.3) dès la première version, même s'ils arrivent en P1. |
| 6.2 | Page détail produit (onglets Bénéfices/Description/Ingrédients/Avis) | Les deux | P0 | ⚪ | `@radix-ui/react-tabs` à ajouter (`npm install @radix-ui/react-tabs`, seul paquet Radix manquant). `Storefront\ProductController::show()` → `pages/storefront/Product.tsx`. |
| 6.3 | Sélecteur de quantité | Front | P0 | ⚪ | Composant contrôlé simple, borné par `product_variants.stock_quantity` côté client (revalidation serveur obligatoire à l'ajout panier, cf. 8.1). |
| 6.4 | Prix barré / promotions produit | Les deux | P1 | ⚪ | Colonne `compare_at_price_cents` déjà prévue sur `product_variants`. Affichage front conditionnel si renseignée et supérieure au prix courant. |
| 6.5 | Guide de choix produit (questionnaire) | Les deux | P2 | ⚪ | Logique conditionnelle côté front (pas de state serveur nécessaire dans une première version) qui redirige vers une liste filtrée du catalogue selon les réponses (type de peau `skin_types` jsonb). |

### 7. SEO & recherche catalogue

| # | Tâche | Dev | Priorité | Statut | Détails |
| --- | --- | --- | --- | --- | --- |
| 7.1 | Slugs SEO produits/catégories | Backend | P1 | ⚪ | `spatie/laravel-sluggable`, généré via Observer `saving`. Colonne `slug` unique déjà prévue sur `products`/`categories`. |
| 7.2 | Recherche produits (Scout + Meilisearch) | Les deux | P1 | ⚪ | Ne pas bloquer le MVP dessus (voir `ARCHITECTURE.md` §5) : une recherche `ILIKE` + index trigram `pg_trgm` PostgreSQL suffit tant que le catalogue reste modeste. Passer à Meilisearch si la tolérance aux fautes de frappe devient nécessaire. Indexer uniquement `status = published`. |
| 7.3 | Filtres / tri catalogue | Les deux | P1 | ⚪ | Filtres par catégorie, marque, `skin_types` (index GIN sur le jsonb si besoin), fourchette de prix. Query string synchronisée avec l'état des filtres côté front. |

---

## PHASE 3 — Panier & commande

### 8. Panier

| # | Tâche | Dev | Priorité | Statut | Détails |
| --- | --- | --- | --- | --- | --- |
| 8.1 | Panier serveur | Backend | P0 | ⚪ | `bumbummen99/shoppingcart`. Tables `carts`/`cart_items` (voir `DATA_MODEL.md`), `session_token` pour les invités. Le panier serveur reste la source de vérité même avec le store Zustand côté client. |
| 8.2 | Calculs monétaires (totaux, remises) | Backend | P0 | ⚪ | `brick/money` — jamais de calcul en float. Les montants de coupons/cadeaux à paliers sont **revalidés côté serveur au moment du paiement**, jamais fait confiance au total calculé côté client. |
| 8.3 | State panier frontend (Zustand) | Front | P0 | ⚪ | `resources/js/stores/cart-store.ts`. État **optimiste** uniquement (UI instantanée) — resynchronisation avec la réponse Inertia après chaque action pour éviter toute divergence, en particulier sur les cadeaux à paliers (10.2). |

### 9. Checkout & paiement

| # | Tâche | Dev | Priorité | Statut | Détails |
| --- | --- | --- | --- | --- | --- |
| 9.1 | Tunnel checkout (adresse, livraison) | Les deux | P0 | ⚪ | Table `addresses` (`type` shipping/billing, `is_default`). `zod` + `react-hook-form` pour la validation formulaire front, `Form Request` Laravel en miroir côté serveur (ne jamais valider uniquement côté client). Invité autorisé jusqu'au paiement (voir `ARCHITECTURE.md`). |
| 9.2 | Paiement Stripe (Checkout / Payment Element) | Les deux | P0 | ⚪ | `POST /checkout` crée l'`Order` en `pending` + un `PaymentIntent` Stripe. Flux complet documenté dans `ARCHITECTURE.md` §4. |
| 9.3 | Apple Pay / Google Pay (Stripe Payment Element) | Les deux | P1 | ⚪ | Déjà couvert par `stripe-php`, uniquement de la config Payment Element côté front + domaine à vérifier dans le dashboard Stripe — pas de package supplémentaire. |
| 9.4 | Webhook Stripe (confirmation paiement) | Backend | P0 | ⚪ | Route dédiée hors CSRF/session (`routes/webhooks.php`), signature `Stripe-Signature` vérifiée. Sur `payment_intent.succeeded` : `Order.status = paid`, décrément stock (4.4), Job Horizon pour l'email de confirmation (12.2). Jamais confirmer un paiement sur le seul retour navigateur. |
| 9.5 | Historique commandes (espace client) | Les deux | P1 | ⚪ | `AccountController::orders()` → `pages/account/Orders.tsx`, protégé par `middleware('auth')`. Liste `orders` + `order_items` de l'utilisateur connecté. |

### 10. Promotions & cadeaux

| # | Tâche | Dev | Priorité | Statut | Détails |
| --- | --- | --- | --- | --- | --- |
| 10.1 | Gestion codes promo / coupons | Les deux | P2 | ⚪ | Table `coupons` déjà prévue (`type` percentage/fixed, `usage_limit`, `times_used`). Application côté panier + revalidation au paiement (8.2). |
| 10.2 | Cadeaux à paliers (seuils panier) | Les deux | P2 | ⚪ | Module le plus coûteux du domaine panier — isolé dans `app/Actions/Cart/SyncThresholdGifts.php` (voir `ARCHITECTURE.md`/`DATA_MODEL.md`). Réévalue `gift_threshold_rules` à chaque recalcul panier, ajoute/retire les lignes `cart_gift_items` ; prix forcé à 0 côté commande, jamais modifiable par le client. |

---

## PHASE 4 — Livraison

### 11. Intégration Sendcloud

| # | Tâche | Dev | Priorité | Statut | Détails |
| --- | --- | --- | --- | --- | --- |
| 11.1 | Intégration Sendcloud (calcul tarifs) | Backend | P1 | ⚪ | Pas de SDK PHP officiel — appels REST via la façade `Http`, encapsulés dans `app/Services/SendcloudService.php`. Utilise `weight_grams` sur `product_variants` pour le calcul. |
| 11.2 | Génération étiquettes livraison | Backend | P1 | ⚪ | Déclenché depuis l'admin (16.3) une fois la commande `paid`/`processing`. Crée/complète l'enregistrement `shipments` (voir `DATA_MODEL.md`). |
| 11.3 | Suivi colis (tracking) | Les deux | P2 | ⚪ | `shipments.tracking_number`/`tracking_url` exposés côté espace client (9.5) et dans l'email d'expédition (12.3). |

---

## PHASE 5 — Emails & notifications

### 12. Emails transactionnels

| # | Tâche | Dev | Priorité | Statut | Détails |
| --- | --- | --- | --- | --- | --- |
| 12.1 | Setup Resend (mail driver) | Backend | P0 | ⚪ | `resend/resend-laravel`, `MAIL_MAILER=resend` + `RESEND_KEY` (voir `STACK.md` §5). Tester un envoi réel en sandbox avant de brancher les Notifications. |
| 12.2 | Email confirmation commande | Backend | P0 | ⚪ | Notification/Mailable Laravel natif, déclenché par le Job Horizon lancé depuis le webhook Stripe (9.4). |
| 12.3 | Email expédition/suivi | Backend | P1 | ⚪ | Déclenché à la transition `shipments.status = shipped`, inclut `tracking_url` (11.3). |

### 13. Queues & newsletter

| # | Tâche | Dev | Priorité | Statut | Détails |
| --- | --- | --- | --- | --- | --- |
| 13.1 | Queues Horizon + Redis | Backend | P1 | ⚪ | `laravel/horizon` + Redis serveur. `QUEUE_CONNECTION=redis` en dev/staging/prod — jamais `sync` une fois les jobs emails/stock/indexation en place. |
| 13.2 | Newsletter (opt-in) | Les deux | P2 | ⚪ | Table `newsletter_subscribers` (`consent_at`, `unsubscribed_at`) déjà prévue. Formulaire front simple + endpoint Laravel, RGPD : consentement explicite obligatoire. |

---

## PHASE 6 — Avis clients

> Note d'effort : module non trivial — modération, agrégation des notes, anti-spam/fake reviews,
> contrainte "avoir acheté le produit" avant de pouvoir noter. Prévoir une vraie Action/Service
> dédiée (`app/Actions/Reviews/SubmitReview.php`) plutôt qu'un simple CRUD.

### 14. Modèle & affichage des avis

| # | Tâche | Dev | Priorité | Statut | Détails |
| --- | --- | --- | --- | --- | --- |
| 14.1 | Modèle Review (note, commentaire, produit, user) | Backend | P1 | ⚪ | Table `reviews`, contrainte unique `(product_id, order_item_id)` pour empêcher plusieurs avis sur le même achat — `order_item_id` sert de preuve d'achat. |
| 14.2 | Affichage note moyenne par produit | Les deux | P1 | ⚪ | Ne pas recalculer à la volée : colonnes dénormalisées `reviews_avg_rating`/`reviews_count` sur `products`, mises à jour par un Observer Eloquent (création/approbation/suppression d'un avis). |

### 15. Dépôt & modération des avis

| # | Tâche | Dev | Priorité | Statut | Détails |
| --- | --- | --- | --- | --- | --- |
| 15.1 | Formulaire dépôt d'avis (client ayant acheté) | Les deux | P1 | ⚪ | `zod` + `react-hook-form` (déjà requis Phase 3). N'apparaît que si un `order_item` sans avis existe pour ce client sur ce produit. |
| 15.2 | Modération avis (admin) | Backend | P1 | ⚪ | `filament/filament` (16.1). `status` = `pending`/`approved`/`rejected`, un avis n'impacte `reviews_avg_rating` qu'une fois `approved`. |
| 15.3 | Photos dans les avis | Les deux | P2 | ⚪ | Table `review_photos`, même pattern Cloudinary que 5.1 (stockage `public_id` uniquement). |
| 15.4 | Q&A produit | Les deux | P2 | ⚪ | Non modélisé dans `DATA_MODEL.md` actuel — à concevoir (table `product_questions`/`product_answers`) si retenu, sur le même modèle de modération que les avis. |

---

## PHASE 7 — Gestion interne (admin)

> L'admin est construit avec **Inertia + React (custom)**, le même stack que le storefront — pas
> de Filament/Livewire ni d'autre framework admin externe (voir `ARCHITECTURE.md` §3). C'est un
> troisième dossier de pages (`resources/js/pages/admin/`) protégé par un middleware de rôle
> Spatie sur `routes/admin.php`, pas un sous-site indépendant. D'où le type **Les deux** : chaque
> écran nécessite un contrôleur/Form Request (back) et une page React (front), il n'y a pas de
> génération automatique façon Filament.

### 16. Back-office admin (Inertia/React)

| # | Tâche | Dev | Priorité | Statut | Détails |
| --- | --- | --- | --- | --- | --- |
| 16.1 | Dashboard admin | Les deux | P0 | ⚪ | `routes/admin.php` monté sur `/admin` (middleware `auth` + `role:admin\|staff\|support` Spatie), layout `layouts/admin/admin-layout.tsx` (sidebar + header, sur le modèle de `storefront-layout.tsx`), composant `DataTable` réutilisable (`components/admin/`, sur `Table` shadcn/ui). Permissions scopées via Spatie (2.2) — un `support` ne doit pas voir les prix catalogue, un `staff` ne doit pas voir les remboursements sans permission dédiée. |
| 16.2 | CRUD produits/catégories | Les deux | P0 | ⚪ | `app/Http/Controllers/Admin/ProductController.php` (resource controller) + pages `pages/admin/products/{Index,Create,Edit}.tsx` — inclut l'upload Cloudinary (5.1) et la gestion des axes de variantes (4.2). |
| 16.3 | Gestion commandes (statuts, remboursements) | Les deux | P0 | ⚪ | `Admin/OrderController.php` + page dédiée + Action `RefundOrder` → API Stripe → table `refunds`. Déclenche aussi la génération d'étiquette Sendcloud (11.2). |

### 17. Documents & exports

| # | Tâche | Dev | Priorité | Statut | Détails |
| --- | --- | --- | --- | --- | --- |
| 17.1 | Facturation PDF (dompdf) | Backend | P1 | ⚪ | `barryvdh/laravel-dompdf`, généré depuis les données `orders`/`order_items` déjà en snapshot (pas besoin de retourner sur le catalogue courant). |
| 17.2 | Export CSV (commandes, clients) | Backend | P2 | ⚪ | Laravel natif (`Storage`/`Response::streamDownload`), pas de package. |
| 17.3 | KPIs / stats ventes (Recharts) | Les deux | P2 | ⚪ | `recharts` utilisé dans une page React du dashboard admin (`pages/admin/Dashboard.tsx`), pas un widget Filament — installer seulement à ce moment-là (`npm install recharts`, voir `STACK.md`). |

---

## PHASE 8 — Contenu & SEO

### 18. Blog

| # | Tâche | Dev | Priorité | Statut | Détails |
| --- | --- | --- | --- | --- | --- |
| 18.1 | Blog (modèle Article) | Backend | P2 | ⚪ | Table `articles` déjà prévue (`status` draft/published, `cover_image_path`). |
| 18.2 | Liaison article ↔ produit (cross-sell éditorial) | Backend | P2 | ⚪ | Pivot `article_product`. |

### 19. SEO technique

| # | Tâche | Dev | Priorité | Statut | Détails |
| --- | --- | --- | --- | --- | --- |
| 19.1 | Sitemap XML | Backend | P1 | ⚪ | `spatie/laravel-sitemap`, généré à partir des `products`/`categories`/`articles` publiés. |
| 19.2 | Meta tags dynamiques (SEOTools) | Les deux | P1 | ⚪ | `artesaos/seotools` côté back, injecté dans le `<head>` rendu par Inertia côté front (`meta_title`/`meta_description` déjà prévus sur `products`). |

---

## PHASE 9 — Pages légales & conformité

### 20. Pages légales

| # | Tâche | Dev | Priorité | Statut | Détails |
| --- | --- | --- | --- | --- | --- |
| 20.1 | Mentions légales | Front | P0 | ⚪ | Page statique Inertia, pas de modèle back nécessaire. |
| 20.2 | CGV | Front | P0 | ⚪ | Idem — contenu statique. |
| 20.3 | Politique de confidentialité (RGPD) | Front | P0 | ⚪ | Idem. |
| 20.4 | Politique de livraison | Front | P0 | ⚪ | Idem. |
| 20.5 | Politique de retours/remboursements | Front | P0 | ⚪ | Idem. |
| 20.6 | Bandeau consentement cookies | Front | P0 | ⚪ | Composant React custom, pas de package tiers. Bloque tout chargement de pixel marketing (27.1) tant que le consentement n'est pas donné (voir `ARCHITECTURE.md` §8). |

---

## PHASE 10 — Qualité & monitoring

### 21. Tests & qualité

| # | Tâche | Dev | Priorité | Statut | Détails |
| --- | --- | --- | --- | --- | --- |
| 21.1 | Tests (Pest) | Backend | P1 | ⚪ | Déjà dans le starter. Couvrir en priorité les Actions non triviales : `SyncThresholdGifts` (10.2), `PlaceOrder`, `RefundOrder`, `SubmitReview`. |
| 21.2 | Analyse statique (Larastan) | Backend | P2 | ⚪ | Déjà dans le starter (`composer ci:check`). |

### 22. Monitoring

| # | Tâche | Dev | Priorité | Statut | Détails |
| --- | --- | --- | --- | --- | --- |
| 22.1 | Monitoring erreurs (Sentry) | Les deux | P1 | ⚪ | `sentry/sentry-laravel`, DSN par environnement (`STACK.md` §5). À activer avant toute mise en ligne publique (Jalon 2 dans `ROADMAP.md`). |
| 22.2 | Debug local (Telescope/Debugbar) | Backend | P1 | ⚪ | `laravel/telescope` + `barryvdh/laravel-debugbar`, **dev only** — ne jamais activer en production. |

---

## PHASE 11 — Fidélisation & croissance (grand site complet)

> P3 = ambitions long terme, à ne considérer qu'une fois le cœur de l'expérience (P0/P1) stable
> et le trafic réel observé — évite de construire des systèmes de fidélité/abonnement pour un
> site qui n'a pas encore de clients récurrents.

### 23. Fidélisation

| # | Tâche | Dev | Priorité | Statut | Détails |
| --- | --- | --- | --- | --- | --- |
| 23.1 | Cartes cadeaux (gift cards) | Les deux | P2 | ⚪ | Extension du modèle `coupons`/`orders`, à concevoir (pas encore dans `DATA_MODEL.md`). |
| 23.2 | Programme de fidélité (points/paliers) | Les deux | P2 | ⚪ | Module custom, pas de package identifié — à modéliser au moment venu. |
| 23.3 | Parrainage / referral | Les deux | P2 | ⚪ | Code de parrainage + réduction filleul/parrain, probablement branché sur le système `coupons`. |

### 24. Relance & merchandising

| # | Tâche | Dev | Priorité | Statut | Détails |
| --- | --- | --- | --- | --- | --- |
| 24.1 | Relance panier abandonné (email automatisé) | Backend | P1 | ⚪ | Job planifié Horizon (13.1) qui détecte un `cart` inactif depuis X heures avec `cart_items` non transformé en `order`. |
| 24.2 | Bundles / routines packagées | Les deux | P1 | ⚪ | Nouveau regroupement de `product_variants` à prix réduit — à modéliser (pas encore dans `DATA_MODEL.md`). |
| 24.3 | Mega-menu catégories (navigation riche) | Front | P1 | ⚪ | `NavigationMenu` déjà présent (Radix, dans le starter), branché sur l'arbre `categories` (`parent_id`/`position`). |
| 24.4 | Recommandations personnalisées | Les deux | P2 | ⚪ | "Souvent achetés ensemble" / "tu pourrais aimer" — logique à définir (co-achat via `order_items`, ou service tiers). |

### 25. Internationalisation

| # | Tâche | Dev | Priorité | Statut | Détails |
| --- | --- | --- | --- | --- | --- |
| 25.1 | Multi-langue (FR/EN) | Les deux | P2 | ⚪ | `laravel-lang/lang` ou i18n natif Laravel côté back + `resources/js` côté front — approche à évaluer avant de commencer, impacte tous les champs de contenu (`products`, `articles`...). |
| 25.2 | Multi-devise | Les deux | P3 | ⚪ | `brick/money` (déjà requis en 8.2) gère nativement le multi-devise — surtout un travail de exposition/conversion côté front à ce stade. |

### 26. Support & self-service

| # | Tâche | Dev | Priorité | Statut | Détails |
| --- | --- | --- | --- | --- | --- |
| 26.1 | Chat / support client | Front | P2 | ⚪ | Service tiers externe (Crisp/Tawk), simple script embarqué — pas de package Composer/npm, pas de modèle back. |
| 26.2 | Portail self-service retours/échanges | Les deux | P2 | ⚪ | S'appuie sur `orders`/`order_items`/`refunds` existants — à concevoir. |
| 26.3 | Wishlist partageable (lien public) | Les deux | P2 | ⚪ | Table `wishlists` déjà prévue (`user_id`, `product_id`, contrainte unique) ; "partageable" nécessite un token/slug public à ajouter. |
| 26.4 | Réachat rapide / abonnement récurrent | Les deux | P3 | ⚪ | `laravel/cashier` si abonnement facturé récurrent via Stripe — uniquement si des produits consommables s'y prêtent. |

### 27. Marketing & permissions avancées

| # | Tâche | Dev | Priorité | Statut | Détails |
| --- | --- | --- | --- | --- | --- |
| 27.1 | Pixels marketing (Meta, TikTok, Google Ads) | Front | P1 | ⚪ | Scripts tiers chargés conditionnellement, uniquement après consentement du bandeau cookies (20.6) — jamais avant, conformité RGPD. |
| 27.2 | Permissions admin fines par rôle | Backend | P2 | ⚪ | `spatie/laravel-permission` (déjà requis en 2.2) — granularité type "gestionnaire stock" vs "support client" via les permissions `products.manage`/`orders.manage`/etc. déjà listées dans `DATA_MODEL.md`. |

---

## Estimation d'effort par famille de features (repère rapide)

### Facile (peu d'effort dev)

Prix barré (6.4), carrousel images (5.2), onglets fiche produit (6.2), champ liste INCI (4.3),
pages légales statiques (20.1-20.6), sélecteur de quantité (6.3).

### Effort modéré

Blog + liaison article↔produit (18.1-18.2, CRUD + relation many-to-many), Apple Pay/Google Pay
(9.3, config Stripe Payment Element, pas de package tiers), guide de choix produit (6.5, logique
conditionnelle front + UX).

### Plus complexe (vrai module à concevoir)

- **Avis clients avec notation** (14-15) : modération, agrégation des notes, anti-fake-review, photos.
- **Cadeaux à paliers** (10.2) : calcul du total panier en temps réel, ajout/retrait automatique d'un
  produit "gratuit" selon seuils franchis, gestion du cas où le client repasse sous le seuil.
  Isolé dans `app/Actions/Cart/SyncThresholdGifts.php`.

Rien n'est hors de portée en solo sur cette stack — mais ces deux derniers points sont ceux qui
demanderont le plus de temps réel de développement à cause des cas limites à couvrir proprement.
