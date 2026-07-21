# Modèle de données

Conventions Laravel/Eloquent + PostgreSQL. Tables au pluriel snake_case, clés étrangères `xxx_id`.
Prix stockés en **centimes (integer)**, jamais en float.

## Vue d'ensemble des entités

```
User ──< Address
User ──< Order ──< OrderItem >── ProductVariant
User ──< Wishlist >── Product
User ──< Review >── Product
User >──< Role (Spatie)

Brand ──< Product
Category ──< Product (many-to-many via product_category, catégories imbriquées)
Product ──< ProductVariant
Product ──< ProductOption ──< ProductOptionValue
ProductVariant >──< ProductOptionValue (pivot variant_option_values)
Product ──< ProductImage
ProductVariant ──< InventoryMovement

Cart ──< CartItem >── ProductVariant
Cart ──< CartGiftItem  (cadeaux à paliers, ajoutés automatiquement)

Order ──< Payment
Order ──< Shipment
Order ──< Refund
Coupon ──< Order

Article (blog) >──< Product (cross-sell éditorial)
GiftThresholdRule ──< GiftThresholdRuleReward
```

## Comptes utilisateurs (fourni par le starter kit)

### users
| Colonne | Type | Notes |
| --- | --- | --- |
| id | bigint pk | |
| name | string | |
| email | string unique | |
| email_verified_at | timestamp nullable | |
| password | string (hashed) | |
| two_factor_secret | text nullable | chiffré, géré par Fortify |
| two_factor_recovery_codes | text nullable | chiffré, géré par Fortify |
| two_factor_confirmed_at | timestamp nullable | |
| remember_token | string nullable | |
| created_at / updated_at | timestamps | |

> Table déjà migrée par le starter (`0001_01_01_000000_create_users_table` +
> `add_two_factor_columns_to_users_table`) — pas une migration à écrire, juste le socle sur lequel
> se branchent `addresses`, `orders`, `wishlists`, `reviews` et les rôles Spatie (2.1/2.2).

### passkeys
| Colonne | Type | Notes |
| --- | --- | --- |
| id | bigint pk | |
| user_id | bigint fk(users) | |
| name | string | libellé choisi par l'utilisateur (ex. "MacBook Touch ID") |
| credential_id | string unique | |
| credential | text | données WebAuthn, gérées par `laravel/passkeys` |
| last_used_at | timestamp nullable | |
| created_at / updated_at | timestamps | |

> Fournie par `laravel/passkeys` (déjà dans le starter, voir `STACK.md`). Authentification sans
> mot de passe, en complément de Fortify.

## Gestion des rôles (Spatie Laravel-Permission)

- Rôles de base : `admin`, `staff` (gestion stock/commandes), `support` (accès commandes/avis
  uniquement, pas catalogue ni finance), `customer` (implicite, pas de rôle Spatie nécessaire).
- Permissions granulaires suggérées : `products.manage`, `orders.manage`, `orders.refund`,
  `reviews.moderate`, `content.manage`, `settings.manage`.
- Tables générées automatiquement par le package : `roles`, `permissions`, `model_has_roles`,
  `model_has_permissions`, `role_has_permissions`.

## Catalogue

### brands
| Colonne | Type | Notes |
| --- | --- | --- |
| id | bigint pk | |
| name | string | |
| slug | string unique | |
| description | text nullable | |
| logo_path | string nullable | Cloudinary public_id ou URL |
| country_of_origin | string nullable | ex. "Corée du Sud" |
| created_at / updated_at | timestamps | |

### categories
| Colonne | Type | Notes |
| --- | --- | --- |
| id | bigint pk | |
| parent_id | bigint nullable fk(categories) | catégories imbriquées (arbre) |
| name | string | |
| slug | string unique | |
| position | integer | ordre d'affichage / mega-menu |

### products
| Colonne | Type | Notes |
| --- | --- | --- |
| id | bigint pk | |
| brand_id | bigint fk(brands) nullable | |
| name | string | |
| slug | string unique | |
| short_description | string nullable | |
| description | text | |
| ingredients_inci | text nullable | liste INCI complète (obligation légale cosmétique) |
| how_to_use | text nullable | |
| skin_types | jsonb nullable | ex. `["sèche","grasse","mixte"]` |
| period_after_opening | string nullable | PAO, ex. "12M" |
| status | enum(`draft`,`published`,`archived`) | |
| is_featured | boolean default false | |
| meta_title / meta_description | string nullable | SEO |
| published_at | timestamp nullable | |
| deleted_at | timestamp nullable | SoftDeletes |
| created_at / updated_at | timestamps | |

### product_category (pivot)
`product_id`, `category_id`

### Variantes à axes multiples (contenance × teinte × conditionnement)

Un produit K-Beauty peut varier sur plusieurs axes indépendants (ex. contenance **et** teinte).
Modéliser ça avec une simple colonne `label` sur `product_variants` ne suffit pas si tu veux
filtrer/afficher un sélecteur par axe (comme sur uniikon.com : "50ml / 100ml" + "Teinte 01 / 02").

**product_options** — les axes de variation, par produit (ex. "Contenance", "Teinte")
| id | product_id fk | name | position |

**product_option_values** — les valeurs possibles d'un axe (ex. "50 ml", "100 ml")
| id | product_option_id fk | value | position |

**product_variants**
| Colonne | Type | Notes |
| --- | --- | --- |
| id | bigint pk | |
| product_id | bigint fk(products) | |
| sku | string unique | |
| price_cents | integer | prix TTC en centimes |
| compare_at_price_cents | integer nullable | prix barré |
| currency | string default 'EUR' | |
| weight_grams | integer nullable | calcul livraison |
| stock_quantity | integer default 0 | |
| is_default | boolean default false | |
| position | integer | |

**variant_option_values** (pivot) — combinaison qui définit une variante précise
`product_variant_id`, `product_option_value_id`

> Exemple : le produit "Sérum Centella" a l'axe "Contenance" (30ml/50ml). Une variante = une ligne
> `product_variants` liée à une valeur `product_option_values` ("50 ml") via le pivot. Si un futur
> produit a 2 axes (Contenance × Teinte), chaque variante est liée à 2 lignes du pivot — le modèle
> supporte nativement N axes sans migration supplémentaire.

### product_images
| id | product_id fk | product_variant_id fk nullable | path (Cloudinary) | alt_text | position |

### inventory_movements
| id | product_variant_id fk | type(enum: `restock`,`sale`,`return`,`adjustment`) | quantity | note | created_at |

## Panier

### carts
| id | user_id fk nullable | session_token string nullable | currency | created_at / updated_at |

### cart_items
| id | cart_id fk | product_variant_id fk | quantity | unit_price_cents (snapshot) |

### cart_gift_items (cadeaux à paliers — ajoutés automatiquement, non modifiables par le client)
| id | cart_id fk | product_variant_id fk | gift_threshold_rule_id fk | added_automatically boolean default true |

### gift_threshold_rules
| id | name | min_subtotal_cents | starts_at nullable | expires_at nullable | is_active |

### gift_threshold_rule_rewards
| id | gift_threshold_rule_id fk | product_variant_id fk (le cadeau offert) | quantity default 1 |

> Logique métier : à chaque recalcul du panier (ajout/retrait d'article), une Action dédiée
> (`app/Actions/Cart/SyncThresholdGifts.php`) réévalue les règles actives par `min_subtotal_cents`
> décroissant, ajoute/retire les lignes `cart_gift_items` correspondantes. Ne jamais laisser le
> client modifier ou payer une ligne de `cart_gift_items` (prix forcé à 0 au moment de la commande).

## Adresses & comptes

### addresses
| id | user_id fk | type(enum: `shipping`,`billing`) | full_name | line1 | line2 nullable | postal_code | city | country_code | phone nullable | is_default |

## Commandes

### orders
| Colonne | Type | Notes |
| --- | --- | --- |
| id | bigint pk | |
| user_id | bigint fk nullable | nullable au niveau schéma, mais toujours renseigné en pratique — le checkout invité a été retiré (9.2, `FEATURES.md`), un compte est requis avant `/commande` |
| order_number | string unique | ex. `KB-2026-00001` |
| status | enum(`pending`,`paid`,`processing`,`shipped`,`delivered`,`cancelled`,`refunded`) | |
| shipping_address_id | fk(addresses) | |
| billing_address_id | fk(addresses) | |
| subtotal_cents | integer | |
| discount_cents | integer default 0 | |
| shipping_cents | integer | |
| tax_cents | integer | |
| total_cents | integer | |
| currency | string default 'EUR' | |
| coupon_id | fk(coupons) nullable | |
| notes | text nullable | |
| placed_at | timestamp | |

### order_items
| id | order_id fk | product_variant_id fk | product_name (snapshot) | variant_label (snapshot) | unit_price_cents | quantity | total_cents | is_gift boolean default false |

### payments
| id | order_id fk | provider(enum: `stripe`) | provider_payment_id | status(enum: `pending`,`succeeded`,`failed`,`refunded`) | amount_cents | paid_at nullable |

### refunds
| id | order_id fk | payment_id fk | amount_cents | reason nullable | status(enum: `pending`,`succeeded`,`failed`) | created_at |

### shipments
| id | order_id fk | carrier (Sendcloud) | tracking_number nullable | tracking_url nullable | status(enum: `pending`,`shipped`,`delivered`) | shipped_at nullable | delivered_at nullable |

### coupons
| id | code unique | type(enum: `percentage`,`fixed`) | value | min_order_cents nullable | starts_at | expires_at | usage_limit nullable | times_used default 0 | is_active |

## Avis clients

### reviews
| Colonne | Type | Notes |
| --- | --- | --- |
| id | bigint pk | |
| product_id | bigint fk(products) | |
| user_id | bigint fk(users) | |
| order_item_id | bigint fk(order_items) nullable | preuve d'achat, contrainte "vérifié" |
| rating | smallint (1-5) | |
| title | string nullable | |
| body | text | |
| status | enum(`pending`,`approved`,`rejected`) default `pending` | |
| created_at | timestamp | |

Contrainte unique `(product_id, order_item_id)` pour empêcher plusieurs avis sur le même achat.

### review_photos
| id | review_id fk | path (Cloudinary) | position |

> Agrégation de la note moyenne : ne pas recalculer à la volée à chaque affichage produit —
> stocker `reviews_avg_rating` et `reviews_count` en colonnes dénormalisées sur `products`,
> mises à jour via un Observer Eloquent sur `Review` (création/approbation/suppression).

## Contenu éditorial

### articles
| id | title | slug unique | excerpt nullable | body | cover_image_path nullable | status(enum: `draft`,`published`) | published_at nullable | author_id fk(users) nullable |

### article_product (pivot cross-sell éditorial)
`article_id`, `product_id`

## Marketing

### newsletter_subscribers
| id | email unique | consent_at | unsubscribed_at nullable |

### wishlists
| id | user_id fk | product_id fk | created_at | contrainte unique `(user_id, product_id)` |

## Notes d'implémentation Laravel

- **PostgreSQL** : utiliser `jsonb` (pas `json`) pour les colonnes comme `skin_types` — indexable
  avec un index GIN si besoin de filtrer par type de peau plus tard.
- Migrations incrémentales, une par entité, jamais un gros fichier monolithique.
- **Enums PHP natifs** (PHP 8.3) castés dans les modèles (`status`, `type`…), pas de string libres.
- `slug` généré via `spatie/laravel-sluggable` ou un Observer `saving`.
- `SoftDeletes` sur `products` et `orders` — ne jamais supprimer un historique de commande, même
  si un produit est retiré du catalogue (les `order_items` gardent un snapshot du nom/prix).
- Factories + seeders réalistes (catalogue K-Beauty de démo) pour construire l'UI avant d'avoir
  de vraies données fournisseur.
- Les montants de `gift_threshold_rules` et `coupons` doivent être revalidés côté serveur au
  moment du paiement (jamais faire confiance au total calculé côté client/Zustand).
