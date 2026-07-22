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
| `product_images` | 5.1 | 🟢 |
| `inventory_movements` | 4.4 | 🟢 |
| `carts` | 8.1 | ⚪ |
| `cart_items` | 8.1 | ⚪ |
| `cart_gift_items` | 10.2 | ⚪ |
| `gift_threshold_rules` | 10.2 | ⚪ |
| `gift_threshold_rule_rewards` | 10.2 | ⚪ |
| `addresses` | 9.1 | 🟢 |
| `orders` | 9.2 | 🟢 |
| `order_items` | 9.2 | 🟢 |
| `payments` | 9.2 | 🟢 |
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
| 2.1 | Auth (Fortify) — login/register/reset password | Les deux | P0 | 🟢 | `FortifyServiceProvider::configureViews()` OK, toutes les routes (login/register/forgot-password/reset-password/2FA/confirm-password) enregistrées et testées : pages Inertia répondent 200, création d'un `User` via factory + hash de mot de passe validés en base PostgreSQL. Envoi réel des emails de reset à revérifier une fois Resend branché (12.1) — actuellement `MAIL_MAILER=log`. |
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
| 4.5 | Alertes stock bas | Backend | P2 | 🟢 | `App\Notifications\LowStockAlert` (canal `mail` uniquement pour l'instant — le vrai envoi Resend n'est branché qu'en 12.1, jusque-là `MAIL_MAILER=log`), déclenchée depuis `StockService::recordMovement()` **uniquement au franchissement** du seuil `config('inventory.low_stock_threshold')` (défaut 5, `LOW_STOCK_THRESHOLD` dans `.env`) — pas à chaque mouvement une fois déjà en dessous, pour ne pas spammer une alerte par vente. Envoyée à tous les `User::role('admin')` (Spatie, 2.2), pas à `staff`/`support`. 4 tests Pest (franchissement notifie, vente restant au-dessus ne notifie pas, vente restant en-dessous ne re-notifie pas, réassort qui repasse au-dessus ne notifie pas). Canal `database` + affichage dans le dashboard admin (16.1) laissés pour plus tard. |

### 5. Médias produits

| # | Tâche | Dev | Priorité | Statut | Détails |
| --- | --- | --- | --- | --- | --- |
| 5.1 | Upload images produits (Cloudinary) | Les deux | P0 | 🟢 | `cloudinary-labs/cloudinary-laravel` incompatible avec Laravel 13 (dernière version ne supporte que `illuminate/support` `^11\|^12`) — utilisation directe du SDK officiel `cloudinary/cloudinary_php` à la place, encapsulé dans `App\Services\CloudinaryService` (`upload()`/`destroy()`/`url()`), instance `Cloudinary\Cloudinary` enregistrée en singleton dans `AppServiceProvider` à partir de `config('services.cloudinary.url')` (`CLOUDINARY_URL` dans `.env`). Ne stocke que le `public_id` en base (`product_images.path`) ; `ProductImage::url()` construit l'URL transformée (`w_/h_/c_fill/q_auto/f_auto`) à la volée, aucun fichier transformé dupliqué en stockage. Table `product_images` (`product_id`, `product_variant_id` nullable, `path`, `alt_text`, `position`) — pas de colonne `is_primary` dédiée : l'image principale est celle avec `position` la plus basse (0), utilisée telle quelle par le futur carrousel storefront (5.2). Upload fait depuis l'admin, sur la page produit (16.2b) : `App\Http\Controllers\Admin\ProductImageController` (`store` multi-fichiers en une requête, `makePrimary` qui décale les positions pour faire passer une image en tête, `destroy`) + `StoreProductImageRequest` (images ≤ 5 Mo chacune, `product_variant_id` optionnel restreint aux variantes du produit courant). Suppression Cloudinary + DB synchronisées (le mock `CloudinaryService` dans les tests évite tout appel réseau réel). `Product::primaryImage()` (`HasOne::ofMany('position', 'min')`) expose l'image de couverture sans dupliquer une requête par produit ; utilisée pour afficher une vignette dans `pages/admin/products/index.tsx` (absente jusqu'ici). 8 tests Pest, Larastan clean. |
| 5.2 | Carrousel images produit | Front | P0 | 🟢 | `resources/js/components/storefront/product-gallery.tsx` — carrousel principal + bande de miniatures + lightbox plein écran (`embla-carousel-react` via `components/ui/carousel.tsx`, shadcn/ui, ajouté ici). Consomme les images triées par `position` (relation `Product::images()`), filtre sur `product_variant_id` quand une variante est sélectionnée (retombe sur toutes les images si le filtre est vide). Lightbox : `components/ui/dialog.tsx` étendu avec `showCloseButton`/`overlayClassName` pour un rendu plein écran fond noir flouté, compteur d'images, miniatures propres, navigation clavier/flèches/swipe (héritée d'Embla). Construite avec une page produit storefront minimale pour la tester réellement (6.2 complet — onglets, avis — reste à faire) : layout `layouts/storefront-layout.tsx` enfin branché dans `app.tsx` (`name.startsWith('storefront/')`, absent jusqu'ici bien que les composants header/footer existaient déjà), route publique `GET produits/{product:slug}` (`routes/storefront.php`, nouveau, `require` depuis `web.php`), `App\Http\Controllers\Storefront\ProductController::show()` (404 si le produit n'est pas `published`). 4 tests Pest, Larastan clean. |

### 6. Pages publiques catalogue

| # | Tâche | Dev | Priorité | Statut | Détails |
| --- | --- | --- | --- | --- | --- |
| 6.1 | Page liste produits (catalogue public) | Les deux | P0 | 🟢 | `App\Http\Controllers\Storefront\CatalogController::index()` → `Inertia::render('storefront/catalog')` (minuscule, cohérent avec `storefront/product` de 5.2 — la casse `Catalog` du descriptif d'origine n'était pas suivie ailleurs dans le code). Route publique `GET produits` (`storefront.products.index`, avant `produits/{product:slug}` dans `routes/storefront.php`). Filtre `status = published` uniquement, pagination Laravel classique (`paginate(24)->withQueryString()`) — `withQueryString()` prépare le terrain pour les filtres (7.3) sans rien à changer ici. Chaque produit expose marque, prix (variante par défaut, même logique que `ProductController::show`) et vignette Cloudinary (image `position` la plus basse). Nouveau composant générique `components/pagination.tsx` (consomme directement le tableau `links` de Laravel, pas de primitive shadcn dédiée dans le starter) — réutilisable tel quel par un futur listing admin paginé. 3 tests Pest (liste avec marque/prix, exclusion des brouillons/archivés, pagination à 24/page), Larastan clean. |
| 6.2 | Page détail produit (onglets Bénéfices/Description/Ingrédients/Mode d'emploi/Avis) | Les deux | P0 | 🟢 | 5 onglets au final (pas 4) pour rester cohérent avec `CONTENT_STRUCTURE.md` §3 qui liste aussi "Mode d'emploi" — le champ `how_to_use` existait déjà en base (4.1) mais n'était affiché nulle part avant. `@radix-ui/react-tabs` ajouté + `components/ui/tabs.tsx` (nouvelle primitive shadcn/ui, absente du starter). `Storefront\ProductController::show()` expose désormais `ingredients_inci`/`how_to_use` en plus des champs déjà présents (5.2). Onglet "Avis" volontairement statique ("Aucun avis pour le moment") : le modèle `reviews` (Phase 6, 14-15) n'existe pas encore. 2 tests Pest ajoutés (contenu ingrédients/mode d'emploi exposé), suite existante de 5.2 inchangée, Larastan clean. |
| 6.3 | Sélecteur de quantité | Front | P0 | 🟢 | Nouveau composant `components/storefront/quantity-selector.tsx` (boutons +/- avec `lucide-react`, champ texte contrôlé), state React local sur `storefront/product`. Borné à `[1, stockQuantity]` côté client uniquement — `Storefront\ProductController::show()` expose désormais `stockQuantity` (variante par défaut). Rupture de stock (`stockQuantity` à 0 ou nul) : sélecteur masqué, message "Rupture de stock." affiché à la place. Revalidation serveur à l'ajout panier reste à faire en 8.1. 1 test Pest ajouté (stock exposé), Larastan clean. |
| 6.4 | Prix barré / promotions produit | Les deux | P1 | 🟢 | Colonne `compare_at_price_cents` déjà présente sur `product_variants` (4.1), fillable côté modèle et validée côté admin (`StoreProductVariantRequest`/`UpdateProductVariantRequest`) — seul l'affichage front et le formulaire admin manquaient. `Storefront\ProductController::show()` et `CatalogController::index()` exposent désormais `compareAtPriceCents` (variante par défaut). Affichage conditionnel (prix barré en `line-through`) sur `storefront/product` et `storefront/catalog` uniquement si renseigné et strictement supérieur au prix courant. Formulaire admin (`admin/products/edit.tsx`) : champ "Prix barré (centimes)" ajouté aux formulaires de création et d'édition de variante, et affiché en lecture seule dans la liste des variantes. 2 tests Pest ajoutés (exposition catalogue + fiche produit), Larastan clean. |
| 6.5 | Guide de choix produit (questionnaire) | Les deux | P2 | 🟢 | Nouvel enum `App\Enums\SkinType` (`seche`/`grasse`/`mixte`/`sensible`/`normale`/`terne`/`mature`/`deshydratee`/`acne`) — seule source de vérité des valeurs possibles pour `skin_types` (jsonb, 4.1), jusqu'ici jamais exposé ni validé nulle part. Page questionnaire `storefront/skin-guide` (route `GET guide-de-choix`, `Storefront\SkinGuideController`) : une question, un choix par type de peau + un lien "Tous les types de peaux" (pas de filtre), redirige en lien simple vers `/produits?skin_type=<valeur>` — pas de state serveur comme prévu au départ. `CatalogController::index()` lit ce paramètre de requête (`SkinType::tryFrom`, ignoré silencieusement si invalide) et filtre via `whereJsonContains('skin_types', ...)` ; expose `activeSkinType` pour afficher un badge de filtre actif + lien de retrait sur `storefront/catalog`. Formulaire admin produit (`create.tsx`/`edit.tsx`) complété avec des cases à cocher `skin_types[]` (le champ était fillable/casté en array depuis 4.1 mais aucun formulaire ne le renseignait) ; validation ajoutée dans `StoreProductRequest`/`UpdateProductRequest`. `CatalogDemoSeeder` mis à jour pour assigner 1 à 2 types de peau aléatoires par produit de démo. 5 tests Pest ajoutés (filtre catalogue, paramètre invalide ignoré, page questionnaire, création produit avec types de peau), Larastan clean. |

### 7. SEO & recherche catalogue

| # | Tâche | Dev | Priorité | Statut | Détails |
| --- | --- | --- | --- | --- | --- |
| 7.1 | Slugs SEO produits/catégories | Backend | P1 | 🟢 | `spatie/laravel-sluggable` (attribut `#[Sluggable(from: 'name', to: 'slug')]` sur `Product` et `Category`, génération automatique via les listeners d'events `eloquent.creating`/`eloquent.updating` du package). Colonne `slug` unique sur `products`/`categories`. Tests couvrant génération auto, collision (suffixe `-1`), et régénération à la mise à jour du nom. |
| 7.2 | Recherche produits (Scout + Meilisearch) | Les deux | P1 | 🟢 | `laravel/scout` + driver `meilisearch` (`config/scout.php`), trait `Searchable` sur `Product`. `shouldBeSearchable()` n'indexe que `status = published`, `toSearchableArray()` expose `name`/`short_description`. Sans `MEILISEARCH_HOST`/`MEILISEARCH_KEY` configurés, Scout retombe sur le driver `collection` (recherche en mémoire, insensible à la casse) — c'est le mode utilisé en local/CI (`phpunit.xml` fixe `SCOUT_DRIVER=collection`), sans dépendance à un serveur Meilisearch. `CatalogController::index()` combine la recherche (`Product::search($q)->keys()`) avec les filtres Eloquent existants (catégorie/marque/prix/tri) via un `whereIn('products.id', ...)`. Champ de recherche sur `/produits` avec recherche "live" (debounce 400ms, pas de bouton submit) et icône `lucide-react`. 2 tests Pest ajoutés (recherche par nom, recherche insensible à la casse sur la description courte), Larastan clean. |
| 7.3 | Filtres / tri catalogue | Les deux | P1 | 🟢 | Filtres catégorie/marque/`skin_types`/fourchette de prix + tri (prix croissant/décroissant, nom) sur `/produits`, synchronisés avec la query string via `router.get` (Inertia, `preserveState`). Le lien vers le détail produit et le bouton retour reconstruisent dynamiquement l'URL avec tous les filtres actifs. 4 tests Pest ajoutés (filtre catégorie, filtre marque, fourchette de prix, tri par prix), Larastan clean. |

---

## PHASE 3 — Panier & commande

### 8. Panier

| # | Tâche | Dev | Priorité | Statut | Détails |
| --- | --- | --- | --- | --- | --- |
| 8.1 | Panier serveur | Backend | P0 | 🟢 | `bumbummen99/shoppingcart` abandonné depuis 2022 (`illuminate/support` plafonné à `^9.0`, incompatible Laravel 13) — `darryldecode/cart` et `binafy/laravel-cart` évalués en remplacement mais écartés aussi (le premier plafonne encore à `^12.0` avec 198 issues ouvertes, le second n'a aucune notion d'invité et recalcule le prix en direct sans snapshot) : `CartService` natif à la place, sur les tables `carts`/`cart_items` telles que définies dans `DATA_MODEL.md`. Invité identifié par un cookie `cart_token` (`Str::random(64)`, chiffré comme tout cookie via `EncryptCookies`, ~400 jours), compte connecté par `user_id` (unique) — un seul panier par visiteur dans les deux cas. `CartService::addItem()`/`updateQuantity()` verrouillent la variante (`lockForUpdate()`) et revalident le stock disponible à chaque ajout/modif (`InsufficientStockException` → erreur de validation 422), complétant la revalidation serveur annoncée mais non faite en 6.3. Prix `unit_price_cents` snapshotté à l'ajout et rafraîchi à chaque cumul de quantité. `MergeGuestCartOnLogin` (écouteur de `Illuminate\Auth\Events\Login`, enregistré dans `AppServiceProvider::boot()`) fusionne le panier invité dans le panier du compte à la connexion — sans ça, le panier invité aurait été perdu au login. `Storefront\CartController` (index/store/update/destroy) + routes `GET/POST /panier`, `PATCH/DELETE /panier/{cartItem}` ; ownership vérifiée (403 si la ligne n'appartient pas au panier du visiteur courant). Page `storefront/cart` minimale (liste, `QuantitySelector` réutilisé de 6.3, suppression) — sans store Zustand, qui reste le périmètre de 8.3. 8 tests Pest (ajout, cumul de quantité, stock insuffisant à l'ajout et à la modification, mise à jour, suppression, isolation entre visiteurs, fusion à la connexion), Larastan clean. |
| 8.2 | Calculs monétaires (totaux, remises) | Backend | P0 | 🟢 | `brick/money` ajouté (`composer.json`). `CartItem::lineTotal(string $currency): Money` (`Money::ofMinor()->multipliedBy($quantity)`) remplace la multiplication `int` brute. `Cart::subtotal()`/`Cart::total()` retournent des `Money` (réduction avec `Money::zero($currency)`) ; `total()` est distinct de `subtotal()` dès maintenant — actuellement identique en l'absence de remise, mais le point d'insertion existe pour que 10.1 (coupons) et 10.2 (cadeaux à paliers) soustraient du sous-total sans changer les appelants. Méthodes `*Cents()` conservées (`getMinorAmount()->toInt()`) pour la sérialisation JSON/Inertia. `Storefront\CartController::index()` expose `totalCents` et `currency` en plus de `subtotalCents`. Front : helper partagé `resources/js/lib/money.ts` (`Intl.NumberFormat('fr-FR', { style: 'currency' })`) remplace le formatage `toFixed(2)` fait main dans `cart.tsx`. 3 tests Feature ajoutés (`CartMoneyTest`: ligne, somme multi-articles, panier vide), Larastan clean. |
| 8.3 | State panier frontend (Zustand) | Front | P0 | 🟢 | `resources/js/stores/cart-store.ts` : store partagé (items, subtotalCents, totalCents, currency, itemCount). État **optimiste** uniquement — `setQuantityOptimistic()`/`removeOptimistic()` mettent à jour l'UI immédiatement au clic, `sync()` resynchronise avec la réponse Inertia authoritative après coup, pour éviter toute divergence (en particulier sur les cadeaux à paliers, 10.2). `App\Support\CartPresenter` centralise la sérialisation panier (utilisée par `CartController::index()` ET par la prop Inertia partagée `cart`, ajoutée dans `HandleInertiaRequests::share()` via `CartService::findExisting()` — lecture seule, aucun panier/cookie créé pour un visiteur qui n'y touche pas). `resources/js/hooks/use-cart-actions.ts` centralise la mutation optimiste + l'appel serveur, réutilisé par `cart.tsx` et le nouveau `MiniCartSheet` (icône panier du header : badge du nombre d'articles + panneau `Sheet` avec aperçu des lignes, scrollable au-delà de 60vh, total et lien vers `/panier`). Larastan clean, 135 tests Pest toujours verts. |

### 9. Checkout & paiement

| # | Tâche | Dev | Priorité | Statut | Détails |
| --- | --- | --- | --- | --- | --- |
| 9.1 | Tunnel checkout (adresse, livraison) | Les deux | P0 | 🟢 | Table `addresses` (`user_id` nullable — conservé au niveau schéma, mais **le checkout invité a été retiré en 9.2**, voir sa note : `type` enum `App\Enums\AddressType` shipping/billing). `App\Http\Controllers\Storefront\CheckoutController::index()` redirige vers `/panier` si le panier est vide ; pré-remplit l'adresse de livraison par défaut (`is_default`) si l'utilisateur connecté en a une. `storeAddress()` (`StoreCheckoutAddressRequest`, validation miroir de zod) crée les lignes `addresses` (livraison + facturation, éventuellement identiques si `billing_same_as_shipping`) et stocke leurs ids en session pour l'étape paiement (9.2). **Sélection d'une adresse enregistrée** (ajouté après coup, une fois l'espace client Adresses disponible en 9.6) : `index()` expose désormais `savedShippingAddresses`/`savedBillingAddresses` (adresses du compte, triées adresse par défaut en tête) ; `storeAddress()` accepte `shipping_address_id`/`billing_address_id` (validés via `Rule::exists` scopé à `user_id`+`type`, donc l'ownership est garanti au niveau de la validation plutôt que par un `abort_if` a posteriori) en alternative à la saisie d'une nouvelle adresse — évite de ressaisir une adresse déjà enregistrée à chaque commande. "Même adresse que la livraison" continue à dupliquer la ligne choisie en une nouvelle ligne `billing` (comportement inchangé, y compris quand la livraison vient d'une adresse existante). Page `storefront/checkout` : formulaire `react-hook-form` + `zod` (`@hookform/resolvers` ajouté), champ adresse avec autocomplétion via l'API Adresse gouvernementale (`api-adresse.data.gouv.fr`, gratuite/sans clé, `components/storefront/address-autocomplete-input.tsx`) qui pré-remplit code postal/ville/pays à la sélection — garde anti-course (`requestId`) pour ignorer une réponse réseau arrivée après une plus récente ; sélecteur d'adresse en boutons radio natifs (livraison et facturation séparément) avec option "Nouvelle adresse" qui révèle le formulaire, présélection de l'adresse par défaut du compte si elle existe. Lien "Passer la commande" ajouté sur `storefront/cart`. 4 tests Pest ajoutés (adresses exposées par type, sélection d'une adresse existante, 403 sur l'adresse d'un autre utilisateur, sélection distincte livraison/facturation). |
| 9.2 | Paiement Stripe (Checkout / Payment Element) | Les deux | P0 | 🟢 | Tables `orders`/`order_items`/`payments` (`DATA_MODEL.md`), enums `OrderStatus`/`PaymentStatus`/`PaymentProvider`. `App\Actions\Orders\PlaceOrder` snapshote le panier (nom produit, libellé variante, prix) dans l'`Order` `pending` — jamais recalculé depuis le catalogue après coup, y compris si le produit change/est supprimé. `App\Services\StripeService` (client Stripe bindé en singleton dans `AppServiceProvider`) crée le `PaymentIntent` (`automatic_payment_methods`, laisse Stripe proposer CB/Wero/Apple Pay/Google Pay selon la config du dashboard). `CheckoutController::pay()` (`GET`+`POST /commande/paiement`, idempotent : réutilise la commande/le paiement `pending` existants au lieu d'en recréer à chaque rechargement de page ou nouvel essai — vérifie le statut réel du `PaymentIntent` via l'API Stripe avant de tenter de modifier son montant, Stripe refusant cette opération une fois `succeeded`) crée `Order`+`OrderItem`+`Payment`, `CheckoutController::confirmation()` sert le `return_url` de `stripe.confirmPayment()` : vide le panier et nettoie la session de checkout (adresses + order id) une fois le paiement confirmé côté Stripe — sans ce nettoyage, un nouveau panier retombait sur l'ancienne commande déjà payée. Le *statut* de la commande, lui, n'est jamais mis à jour depuis ces pages : seul le webhook Stripe (9.4) fait foi. Front : `StripePaymentForm` (`@stripe/react-stripe-js`) monte le Payment Element à partir du `clientSecret`, préremplit nom/email/téléphone/adresse déjà connus (`defaultValues`), affiche un état de chargement explicite et désactive le bouton tant que `stripe`/`elements` ne sont pas prêts (silencieux sinon). **Checkout invité retiré** : middleware `checkout.auth` (`App\Http\Middleware\RequireAccountForCheckout`) sur tout le groupe de routes `/commande*` — un visiteur non connecté est redirigé vers `/login` via `redirect()->guest()` (stocke l'URL demandée en session) et renvoyé automatiquement dessus après connexion/inscription grâce à `redirect()->intended()`, déjà utilisé par les réponses Fortify par défaut. Décision qui simplifie aussi l'email de confirmation (12.2) : toujours `$request->user()->email`, jamais de collecte d'email invité à ajouter. Corrections découvertes en testant en conditions réelles : (1) le schéma zod du formulaire de facturation masqué (`billing_same_as_shipping`) ne doit **pas** porter de contrainte `min(1)` sur des champs présents mais vides — seule une validation conditionnelle (`superRefine`) doit s'appliquer, sinon la soumission échoue silencieusement ; (2) **bouton "Ajouter au panier" manquant sur la fiche produit** depuis 6.3/8.1 (le sélecteur de quantité et l'endpoint existaient mais n'étaient jamais reliés) — ajouté ici avec toast de succès (`Inertia::flash('toast', ...)`, pattern déjà utilisé côté admin). 22 tests Pest (dont réutilisation de la commande pending, rechargement `GET` sans 405, `PaymentIntent` déjà `succeeded`, non-réutilisation après un nouveau panier, redirection invité + retour post-login), Larastan clean. |
| 9.3 | Apple Pay / Google Pay (Stripe Payment Element) | Les deux | P1 | ⚪ | Déjà couvert par `stripe-php`, uniquement de la config Payment Element côté front + domaine à vérifier dans le dashboard Stripe — pas de package supplémentaire. |
| 9.4 | Webhook Stripe (confirmation paiement) | Backend | P0 | 🟢 | `routes/webhooks.php` (`POST /stripe/webhook`), CSRF exclu via `bootstrap/app.php` (`validateCsrfTokens(except: ['stripe/webhook'])`) — reste dans le groupe `web` pour rester cohérent avec le reste du routing, mais Stripe pose sa propre signature donc le jeton CSRF de session n'a pas de sens ici. `App\Services\StripeService::constructWebhookEvent()` vérifie la signature `Stripe-Signature` (`Stripe\Webhook::constructEvent`) avant de faire confiance au payload — signature absente ou invalide → 400, jamais de traitement. `App\Http\Controllers\Webhooks\StripeWebhookController` : sur `payment_intent.succeeded`, retrouve le `Payment` via `provider_payment_id`, passe `Order.status` à `paid` et décrémente le stock de chaque `OrderItem` via `StockService::recordMovement()` (4.4) dans une transaction. Idempotent : Stripe peut livrer le même événement plusieurs fois, donc si le `Payment` est déjà `succeeded` l'événement est acquitté (200) sans re-décrémenter le stock. Un `PaymentIntent` sans `Payment` correspondant est loggé (`Log::warning`) et acquitté sans erreur plutôt que de faire échouer le webhook. Le *statut* de la commande n'est donc mis à jour que par ce webhook, jamais par le retour navigateur (`CheckoutController::confirmation()`, 9.2). Job Horizon pour l'email de confirmation (12.2) reporté à cette tâche : Horizon n'est pas installé (`QUEUE_CONNECTION=database`), Resend non plus (12.1) — rien à déclencher pour l'instant. 6 tests Pest (signature absente/invalide, événement non géré acquitté sans effet de bord, succès → statut + stock + mouvement, rejeu idempotent, `PaymentIntent` orphelin), Larastan clean. |
| 9.5 | Historique commandes (espace client) | Les deux | P1 | 🟢 | `App\Http\Controllers\Storefront\AccountController` (`orders()` + `show()`), routes `mon-compte/commandes` et `mon-compte/commandes/{order}` protégées par `middleware('auth')` (pas `checkout.auth`, qui est spécifique au tunnel de commande). `orders()` paginate (10/page, `withQueryString`) les commandes de `$request->user()` (nouvelle relation `User::orders()`), triées par `placed_at` décroissant — inclut volontairement les commandes encore `pending` (paiement abandonné), le statut français (`OrderStatus::label()`, ajouté ici sur le modèle de `SkinType::label()`) rend cet état explicite plutôt que de les masquer. `show()` vérifie que la commande appartient bien à l'utilisateur (`abort_if(..., 403)`, même pattern que `CartController::authorizeCartItem()`) et affiche le détail complet : adresses de livraison/facturation, lignes avec prix unitaire, sous-total/remise/livraison/taxes/total. **Image produit par ligne de commande** : `order_items.product_image_path` (nouvelle colonne, migration séparée pour ne pas modifier une migration déjà mergée) snapshotée par `PlaceOrder` en même temps que `product_name`/`variant_label` — comme ces champs, elle ne doit plus jamais changer même si l'image du produit change/est supprimée par la suite ; resolue en URL Cloudinary à l'affichage via `CloudinaryService`, comme dans le catalogue. Pages `storefront/account-orders` (liste) et `storefront/account-order` (détail), sous `pages/storefront/` (et non `pages/account/` comme suggéré initialement) pour hériter automatiquement de `StorefrontLayout` via le switch de layout dans `app.tsx`. Lien "Mes commandes" ajouté dans `UserMenuContent`. Prépare le terrain pour le tracking colis (11.3), qui sera affiché sur cette même page de détail une fois `shipments` branché — rien à faire côté 9.5 pour ça, la structure est prête. 7 tests Pest (liste scoped à l'utilisateur + tri, libellé de statut français, état vide, redirection invité sur la liste et le détail, détail consultable par le propriétaire, 403 pour un autre utilisateur), Larastan clean. |
| 9.6 | Gestion des adresses (espace client) | Les deux | P1 | 🟢 | Tâche identifiée après coup : la note de 9.1 renvoyait explicitement une page "Mes adresses" à l'espace client (9.5), mais 9.5 n'a couvert que l'historique des commandes — jamais formalisée comme tâche numérotée jusqu'ici. `App\Http\Controllers\Storefront\AccountAddressController` (`index`/`store`/`update`/`destroy`), routes `mon-compte/adresses*` sous `middleware('auth')` (même groupe que 9.5). `AddressType::label()` ajouté (Livraison/Facturation), sur le modèle de `OrderStatus::label()`. Un seul type d'adresse par défaut par client : `is_default` à `true` désactive automatiquement l'ancien défaut du même `type` pour cet utilisateur (transaction), sinon `CheckoutController::index()` (9.1) ne saurait plus laquelle préremplir. Ownership vérifiée sur `update`/`destroy` (`abort_if(..., 403)`, même pattern que 9.5/`CartController`). Page `storefront/account-addresses` : liste en grille avec badge "Par défaut", formulaire création/édition dans une `Dialog` (shadcn/ui) réutilisant `AddressAutocompleteInput` (9.1). Lien "Mes adresses" ajouté dans `UserMenuContent`. Sélection d'une adresse enregistrée dans le tunnel de commande (9.1) volontairement laissée hors périmètre ici — le tunnel continuait à créer une nouvelle ligne `addresses` à chaque commande ; traité dans une itération suivante, voir la note ajoutée en 9.1. 8 tests Pest (accès invité, isolation + tri par défaut, création, désactivation de l'ancien défaut, modification, 403 sur modification/suppression d'une adresse d'un autre utilisateur, suppression), Larastan clean. |

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
| 12.1 | Setup Resend (mail driver) | Backend | P0 | 🟢 | `resend/resend-laravel` installé (`composer require`, puis `composer dump-autoload` nécessaire — le service provider n'était pas repéré tant que l'autoload n'était pas régénéré après le require). `MAIL_MAILER=resend`, clé lue via `RESEND_API_KEY` (nom de variable du package, différent du `RESEND_KEY` mentionné dans `STACK.md` — corrigé ici). Envoi réel testé en sandbox (`Mail::raw()` via tinker) : sans domaine vérifié sur resend.com/domains, Resend refuse tout destinataire autre que l'adresse propriétaire du compte — testé avec succès vers celle-ci. `MAIL_FROM_ADDRESS` laissé à `hello@example.com` pour l'instant, à changer pour une adresse du domaine une fois celui-ci vérifié (bloquant avant 12.2/12.3 pour toucher de vrais clients). 2 tests Pest (`tests/Feature/ResendMailConfigTest.php` : transport `resend` enregistré, clé lue depuis `services.resend.key`). |
| 12.2 | Email confirmation commande | Backend | P0 | 🟢 | `App\Notifications\OrderConfirmation` (Notification native Laravel, `ShouldQueue` — passe par `QUEUE_CONNECTION=database`, Horizon non installé, cf. 9.4) : liste les articles (nom/variante/total), sous-total, livraison, total. Déclenchée dans `StripeWebhookController::markAsPaid()` juste après le passage à `paid` (donc jamais renvoyée sur rejeu d'un événement déjà traité, l'idempotence du webhook s'applique aussi à l'email). Montants formatés à la main (`number_format`) plutôt qu'avec `Brick\Money::formatTo()`/`NumberFormatter` : l'extension `intl` n'est pas installée sur cet environnement. 2 tests Pest ajoutés à `StripeWebhookTest` (email envoyé au propriétaire de la commande, non renvoyé sur rejeu), Larastan clean. |
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
| 16.1 | Dashboard admin | Les deux | P0 | 🟢 | `routes/admin.php` (`require` depuis `web.php`, même pattern que `settings.php`) monté sur `/admin` derrière `auth` + `role:admin\|staff\|support` — alias `role`/`permission`/`role_or_permission` de Spatie ajoutés explicitement dans `bootstrap/app.php` (pas auto-enregistrés en Laravel 11+ sans Kernel HTTP, sinon `Target class [role] does not exist.`). `App\Http\Controllers\Admin\DashboardController` renvoie `admin/dashboard` avec des stats catalogue (`productsCount`, `publishedProductsCount`, `lowStockVariantsCount` via le seuil de 4.5). Layout `layouts/admin/admin-layout.tsx` + wrapper `layouts/admin-layout.tsx` (même convention gabarit/wrapper que `storefront-layout.tsx`), branché dans `app.tsx` (`name.startsWith('admin/')`). `components/admin/admin-sidebar.tsx` réutilise `AppShell`/`AppContent`/`NavMain`/`NavUser` existants. `components/admin/data-table.tsx` générique sur `components/ui/table.tsx` (primitive shadcn/ui ajoutée ici, absente avant). Rôles de l'utilisateur exposés au front via `auth.roles` (nouveau, dans `HandleInertiaRequests`) pour un scoping futur de la sidebar (16.2/16.3). 6 tests Pest (guest redirigé, rôle manquant → 403, les 3 rôles admin/staff/support accèdent, stats correctes), Larastan clean. |
| 16.2 | CRUD produits/catégories | Les deux | P0 | 🟢 | **Catégories (16.2a) :** `App\Http\Controllers\Admin\CategoryController` (resource, sans `show`) + `StoreCategoryRequest`/`UpdateCategoryRequest`, protégé par `permission:products.manage` (nouveau sous-groupe dans `routes/admin.php` — exclut `support`, qui n'a pas cette permission). Slug auto-généré via `spatie/laravel-sluggable` (attribut `#[Sluggable(from: 'name', to: 'slug')]`, cohérent avec le style `#[Fillable]`/`#[ObservedBy]` du projet). `Category::descendantIds()` empêche un cycle dans l'arbre. Pages `pages/admin/categories/{index,create,edit}.tsx`. 8 tests Pest. **Produits (16.2b) :** `App\Http\Controllers\Admin\ProductController` (resource, sans `show`) + `StoreProductRequest`/`UpdateProductRequest` — mêmes champs que `Product` (4.1) plus `category_ids[]` synchronisé sur le pivot `product_category`. `#[Sluggable]` ajouté à `Product` (absent jusqu'ici — bug réel trouvé en testant en local : `slug` NOT NULL en base, `ProductFactory` le renseignait explicitement donc les tests ne l'avaient jamais remarqué). `ingredients_inci` rendu `required_if:status,published` côté validation (fait écho à `ProductObserver`/4.3, mais en amont pour un message d'erreur exploitable au lieu de l'exception 500 `MissingInciListException`). Axes de variantes gérés via `App\Http\Controllers\Admin\ProductOptionController` (`store`/`destroy` imbriqués sous `/admin/products/{product}/options`, values saisies en zone de texte une par ligne côté front) et les variantes via `App\Http\Controllers\Admin\ProductVariantController` (`store`/`update`/`destroy` sous `/admin/products/{product}/variants`, SKU unique, association aux valeurs d'axes via `optionValues()->sync()`). Après création/modification d'un produit, redirection vers sa page d'édition (pas la liste) pour enchaîner directement sur ses axes/variantes — comportement volontaire, confirmé avec l'utilisateur. Pages `pages/admin/products/{index,create,edit}.tsx`, `edit.tsx` réunit formulaire produit + sections axes/variantes sur la même page. 13 tests Pest (accès `support` refusé, listing, création avec slug auto, publication sans INCI rejetée/acceptée, mise à jour catégories, suppression, ajout/suppression d'axe, ajout/unicité SKU/modification/suppression de variante). `tests/Feature/ProductTest.php::the slug must be unique` réécrit en `the slug is auto-generated and unique-suffixed on collision` (l'ancien test attendait une `QueryException` sur collision de slug, obsolète depuis l'ajout de `#[Sluggable]`). Larastan clean. L'upload Cloudinary (5.1) suit dans une 3e branche. |
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
