# PRD — Site E-commerce Cosmétique Coréenne (K-Beauty)

## 1. Vision

Créer une boutique en ligne de cosmétiques coréens (K-Beauty) au positionnement premium/épuré,
inspirée de [uniikon.com](https://uniikon.com) : esthétique minimaliste, mise en avant produit,
storytelling de marque, expérience d'achat fluide.

> **Hypothèses à valider avec le porteur de projet** (à ajuster dans ce document) :
> - Marché principal : France / UE francophone, extension possible à l'international plus tard.
> - Modèle : vente directe de produits (marque propre et/ou distribution de marques coréennes).
> - Langue de lancement : Français, structure prête pour l'anglais en V2.
> - Devise : EUR.

## 2. Problème à résoudre

Les acheteurs de K-Beauty en France manquent de boutiques en ligne francophones qui combinent :
- une sélection de produits coréens fiables (peu de contrefaçons, dates de péremption claires),
- une expérience visuelle soignée proche des codes esthétiques coréens (épuré, pastel, typographie fine),
- du contenu pédagogique (routines skincare, ordre d'application, types de peau).

## 3. Objectifs produit

| Objectif | Métrique de succès |
|---|---|
| Lancer un MVP vendable | Tunnel d'achat complet (catalogue → panier → paiement → confirmation) fonctionnel |
| Expérience de marque forte | Design cohérent avec la charte (voir `DESIGN_GUIDE.md`) |
| Conversion | Taux de conversion visiteur → achat ≥ 1,5–2 % après 3 mois |
| Fidélisation | Compte client, historique de commandes, wishlist |
| Confiance | Avis clients, pages légales complètes, transparence produit (ingrédients, origine) |

## 4. Personas

**Camille, 27 ans, "K-Beauty enthusiast"**
Suit des créateurs de contenu skincare, connaît déjà les marques coréennes, cherche nouveauté et
routines complètes. Sensible au packaging et au storytelling.

**Sarah, 34 ans, "Découverte"**
A entendu parler de la K-Beauty via les réseaux sociaux, ne connaît pas les marques ni l'ordre
d'application. A besoin de guidance (quiz peau, bundles routine, contenu éducatif).

**Client B2B occasionnel**
Institut de beauté ou revendeur cherchant à commander en gros — hors scope MVP, à considérer en V2.

## 5. Périmètre (voir détail dans `FEATURES.md`)

- **In scope MVP** : catalogue produits/variantes, fiche produit riche, panier, checkout, paiement CB,
  compte client, gestion des commandes, contenu éditorial (routines/blog), pages légales.
- **Hors scope MVP** : marketplace multi-vendeurs, abonnement box mensuelle, programme de fidélité
  à points, app mobile native, B2B.

## 6. Contraintes

- Stack imposée : Laravel 13 + Inertia.js + React 19 + TypeScript + Tailwind CSS v4 + shadcn/ui (voir `ARCHITECTURE.md`).
- Conformité légale FR/UE : CGV, droit de rétractation, RGPD, mentions produits cosmétiques
  (INCI, DLUO/PAO), TVA.
- Paiement : Stripe (CB) au minimum ; PayPal en option V1.1.
- Performance : Core Web Vitals corrects sur mobile (catalogue avec beaucoup d'images produit).

## 7. Risques

- Sourcing produit / droits d'importation de cosmétiques coréens (hors scope technique, à valider côté business).
- Traduction des noms INCI et mentions réglementaires cosmétiques.
- Gestion du stock si produits en rupture fréquente côté fournisseurs coréens.

## 8. Documents liés

- `FEATURES.md` — backlog fonctionnel détaillé
- `DATA_MODEL.md` — modèle de données
- `ARCHITECTURE.md` — architecture technique
- `DESIGN_GUIDE.md` — charte graphique et UI
- `CONTENT_STRUCTURE.md` — arborescence du site et contenus
- `ROADMAP.md` — phasage du développement
