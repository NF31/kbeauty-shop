# Liste des fonctionnalités — Site E-commerce Laravel/Inertia

Légende statut : 🟢 Prêt | 🟡 En cours | ⚪ À faire | 🔴 Bloqué
Priorité : **P0** = critique (MVP) | **P1** = important (V1.1) | **P2** = secondaire (V2+)
Colonne **Package(s)** : nom exact à installer (voir commandes complètes dans `STACK.md`).
"—" = aucun package supplémentaire, juste du code Laravel/React natif ou déjà présent dans le starter.

Stack cible : Laravel + Inertia + React + TypeScript, PostgreSQL, Tailwind CSS, Spatie
Permissions, Filament (admin), Stripe, Sendcloud, Resend, Redis/Horizon, Meilisearch (Scout),
Cloudinary, Zustand, Pest, Larastan, Sentry. Détail des choix et justification dans `ARCHITECTURE.md`.

## Phase 1 — Fondations

| Fonctionnalité | Statut | Priorité | Package(s) |
| --- | --- | --- | --- |
| Setup Laravel + Inertia + React + TypeScript | ⚪ | P0 | déjà dans le starter |
| Setup PostgreSQL + migrations de base | ⚪ | P0 | — (driver `pgsql` natif Laravel) |
| Setup Tailwind CSS | ⚪ | P0 | déjà dans le starter |
| Auth (Fortify) — login/register/reset password | ⚪ | P0 | déjà dans le starter (`laravel/fortify`) |
| Rôles & permissions (Spatie) — admin/client | ⚪ | P0 | `spatie/laravel-permission` |
| Layout général (Navbar, Footer) | ⚪ | P1 | — |
| Validation env variables | ⚪ | P1 | — (`php artisan config:cache` + validation manuelle) |

## Phase 2 — Catalogue produits

| Fonctionnalité | Statut | Priorité | Package(s) |
| --- | --- | --- | --- |
| Modèle Product (nom, description, prix, stock) | ⚪ | P0 | — |
| Modèle Category / Variant | ⚪ | P0 | — |
| Liste INCI ingrédients par produit | ⚪ | P0 | — (simple colonne texte) |
| Upload images produits (Cloudinary) | ⚪ | P0 | `cloudinary-labs/cloudinary-laravel` |
| Carrousel images produit | ⚪ | P0 | — (composant React custom) |
| Page liste produits (catalogue public) | ⚪ | P0 | — |
| Page détail produit (onglets Bénéfices/Description/Ingrédients) | ⚪ | P0 | `@radix-ui/react-tabs` (à ajouter, non présent dans le starter) |
| Sélecteur de quantité | ⚪ | P0 | — |
| Prix barré / promotions produit | ⚪ | P1 | — (champ `compare_at_price_cents`) |
| Slugs SEO produits/catégories | ⚪ | P1 | `spatie/laravel-sluggable` |
| Recherche produits (Scout + Meilisearch) | ⚪ | P1 | `laravel/scout` + `meilisearch/meilisearch-php` |
| Filtres / tri catalogue | ⚪ | P1 | — |
| Gestion stock (décrémentation auto) | ⚪ | P0 | — |
| Alertes stock bas | ⚪ | P2 | — (Notification Laravel native) |
| Guide de choix produit (questionnaire) | ⚪ | P2 | — |

## Phase 3 — Panier & commande

| Fonctionnalité | Statut | Priorité | Package(s) |
| --- | --- | --- | --- |
| Panier serveur | ⚪ | P0 | `bumbummen99/shoppingcart` |
| Calculs monétaires (totaux, remises) | ⚪ | P0 | `brick/money` |
| State panier frontend (Zustand) | ⚪ | P0 | `zustand` |
| Tunnel checkout (adresse, livraison) | ⚪ | P0 | `zod` + `react-hook-form` (validation formulaire) |
| Paiement Stripe (Checkout) | ⚪ | P0 | `stripe/stripe-php` |
| Apple Pay / Google Pay (Stripe Payment Element) | ⚪ | P1 | — (déjà couvert par `stripe/stripe-php`, config Payment Element) |
| Webhook Stripe (confirmation paiement) | ⚪ | P0 | — (route Laravel native + `stripe/stripe-php`) |
| Gestion codes promo / coupons | ⚪ | P2 | — |
| Cadeaux à paliers (seuils panier) | ⚪ | P2 | — (Action/Service dédié, voir `ARCHITECTURE.md`) |
| Historique commandes (espace client) | ⚪ | P1 | — |

## Phase 4 — Livraison

| Fonctionnalité | Statut | Priorité | Package(s) |
| --- | --- | --- | --- |
| Intégration Sendcloud (calcul tarifs) | ⚪ | P1 | — (appel API REST Sendcloud via `Http` facade, pas de SDK officiel PHP) |
| Génération étiquettes livraison | ⚪ | P1 | — (API REST Sendcloud) |
| Suivi colis (tracking) | ⚪ | P2 | — (API REST Sendcloud) |

## Phase 5 — Emails & notifications

| Fonctionnalité | Statut | Priorité | Package(s) |
| --- | --- | --- | --- |
| Setup Resend (mail driver) | ⚪ | P0 | `resend/resend-laravel` |
| Email confirmation commande | ⚪ | P0 | — (Notification/Mailable Laravel natif) |
| Email expédition/suivi | ⚪ | P1 | — |
| Queues Horizon + Redis | ⚪ | P1 | `laravel/horizon` (+ Redis serveur, pas un package PHP) |
| Newsletter (opt-in) | ⚪ | P2 | — |

## Phase 6 — Avis clients

| Fonctionnalité | Statut | Priorité | Package(s) |
| --- | --- | --- | --- |
| Modèle Review (note, commentaire, produit, user) | ⚪ | P1 | — |
| Affichage note moyenne par produit | ⚪ | P1 | — (Observer Eloquent, voir `DATA_MODEL.md`) |
| Formulaire dépôt d'avis (client ayant acheté) | ⚪ | P1 | `zod` + `react-hook-form` (déjà requis en Phase 3) |
| Modération avis (admin) | ⚪ | P1 | `filament/filament` (déjà requis en Phase 7) |
| Photos dans les avis | ⚪ | P2 | `cloudinary-labs/cloudinary-laravel` (déjà requis en Phase 2) |
| Q&A produit | ⚪ | P2 | — |

> Note d'effort : module non trivial — modération, agrégation des notes, anti-spam/fake reviews,
> contrainte "avoir acheté le produit" avant de pouvoir noter. Voir `DATA_MODEL.md` (table `reviews`)
> et prévoir une vraie Action/Service dédiée plutôt qu'un simple CRUD.

## Phase 7 — Gestion interne (admin)

| Fonctionnalité | Statut | Priorité | Package(s) |
| --- | --- | --- | --- |
| Dashboard admin (Filament) | ⚪ | P0 | `filament/filament` |
| CRUD produits/catégories | ⚪ | P0 | `filament/filament` (Resources) |
| Gestion commandes (statuts, remboursements) | ⚪ | P0 | `filament/filament` + `stripe/stripe-php` (remboursement API) |
| Facturation PDF (dompdf) | ⚪ | P1 | `barryvdh/laravel-dompdf` |
| Export CSV (commandes, clients) | ⚪ | P2 | — (Laravel natif, `Storage`/`Response::streamDownload`) |
| KPIs / stats ventes (Recharts) | ⚪ | P2 | `recharts` |

## Phase 8 — Contenu & SEO

| Fonctionnalité | Statut | Priorité | Package(s) |
| --- | --- | --- | --- |
| Blog (modèle Article) | ⚪ | P2 | — |
| Liaison article ↔ produit (cross-sell éditorial) | ⚪ | P2 | — (pivot Eloquent) |
| Sitemap XML | ⚪ | P1 | `spatie/laravel-sitemap` |
| Meta tags dynamiques (SEOTools) | ⚪ | P1 | `artesaos/seotools` |

## Phase 9 — Pages légales & conformité

| Fonctionnalité | Statut | Priorité | Package(s) |
| --- | --- | --- | --- |
| Mentions légales | ⚪ | P0 | — (page statique Inertia) |
| CGV | ⚪ | P0 | — |
| Politique de confidentialité (RGPD) | ⚪ | P0 | — |
| Politique de livraison | ⚪ | P0 | — |
| Politique de retours/remboursements | ⚪ | P0 | — |
| Bandeau consentement cookies | ⚪ | P0 | — (composant React custom, pas de package tiers nécessaire) |

## Phase 10 — Qualité & monitoring

| Fonctionnalité | Statut | Priorité | Package(s) |
| --- | --- | --- | --- |
| Tests (Pest) | ⚪ | P1 | déjà dans le starter (`pestphp/pest`) |
| Analyse statique (Larastan) | ⚪ | P2 | déjà dans le starter (`larastan/larastan`) |
| Monitoring erreurs (Sentry) | ⚪ | P1 | `sentry/sentry-laravel` |
| Debug local (Telescope/Debugbar) | ⚪ | P1 | `laravel/telescope`, `barryvdh/laravel-debugbar` (dev only) |

## Phase 11 — Fidélisation & croissance (grand site complet)

| Fonctionnalité | Statut | Priorité | Package(s) |
| --- | --- | --- | --- |
| Cartes cadeaux (gift cards) | ⚪ | P2 | — (extension du modèle `coupons`/`orders`, voir `DATA_MODEL.md`) |
| Programme de fidélité (points/paliers) | ⚪ | P2 | — (module custom) |
| Parrainage / referral (code de parrainage, réduction filleul+parrain) | ⚪ | P2 | — |
| Relance panier abandonné (email automatisé) | ⚪ | P1 | `laravel/horizon` (job planifié, déjà requis Phase 5) |
| Bundles / routines packagées (kit produits à prix réduit) | ⚪ | P1 | — |
| Mega-menu catégories (navigation riche) | ⚪ | P1 | `NavigationMenu` déjà présent (Radix, dans le starter) |
| Recommandations personnalisées ("souvent achetés ensemble", "tu pourrais aimer") | ⚪ | P2 | — |
| Multi-langue (FR/EN) | ⚪ | P2 | `laravel-lang/lang` ou i18n natif Laravel + `resources/js` (à évaluer) |
| Multi-devise | ⚪ | P3 | `brick/money` (déjà requis Phase 3, gère le multi-devise nativement) |
| Chat / support client | ⚪ | P2 | service tiers externe (Crisp/Tawk, pas un package Composer/npm) |
| Portail self-service retours/échanges | ⚪ | P2 | — |
| Wishlist partageable (lien public) | ⚪ | P2 | — |
| Réachat rapide / abonnement récurrent sur produits consommables | ⚪ | P3 | `laravel/cashier` (si abonnement facturé récurrent via Stripe) |
| Pixels marketing (Meta, TikTok, Google Ads) + bandeau consentement | ⚪ | P1 | — (scripts tiers chargés conditionnellement, pas de package PHP) |
| Permissions admin fines par rôle (Spatie, ex. "gestionnaire stock" vs "support client") | ⚪ | P2 | `spatie/laravel-permission` (déjà requis Phase 1) |

> P3 = ambitions long terme, à ne considérer qu'une fois le cœur de l'expérience (P0/P1) stable
> et le trafic réel observé — évite de construire des systèmes de fidélité/abonnement pour un
> site qui n'a pas encore de clients récurrents.

## Estimation d'effort par famille de features (repère rapide)

### Facile (peu d'effort dev)

Prix barré, carrousel images, onglets fiche produit, champ liste INCI, pages légales statiques,
sélecteur de quantité.

### Effort modéré

Blog + liaison article↔produit (CRUD + relation many-to-many), Apple Pay/Google Pay (config Stripe
Payment Element, pas de package tiers), guide de choix produit (logique conditionnelle front + UX).

### Plus complexe (vrai module à concevoir)

- **Avis clients avec notation** : modération, agrégation des notes, anti-fake-review, photos.
- **Cadeaux à paliers** : calcul du total panier en temps réel, ajout/retrait automatique d'un
  produit "gratuit" selon seuils franchis, gestion du cas où le client repasse sous le seuil.
  À isoler dans une Action/Service dédiée (`app/Actions/Cart/ApplyThresholdGifts.php` ou équivalent).

Rien n'est hors de portée en solo sur cette stack — mais ces deux derniers points sont ceux qui
demanderont le plus de temps réel de développement à cause des cas limites à couvrir proprement.
