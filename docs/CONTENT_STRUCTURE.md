---
title: Arborescence du site & contenus
order: 8
---

# Arborescence du site & contenus

## 1. Plan du site (storefront public)

> Chaque route FR ci-dessous a son miroir `/en/...` (25.1, `routes/storefront.php`) — non
> répété ligne par ligne. Toutes les URLs sont vérifiées contre `php artisan route:list` (à jour
> au 2026-08-05), pas juste planifiées — les éventuels écarts avec ce fichier sont un bug de
> doc à corriger, pas une source de vérité alternative.

```text
/                                   Accueil
/produits                           Catalogue (recherche live + filtres ?category=/?brand=/?sort=... en query string,
                                     pas de route dédiée par catégorie — évite le contenu dupliqué, cf. 19.1)
/produits/[product-slug]            Fiche produit
/marques                            Annuaire des marques (6.6)
/marques/[brand-slug]                Page marque (produits + filtre gamme)
/guide-de-choix                     Guide de choix (quiz orientation produit)
/diagnostic-peau                    Diagnostic peau (Phase 3 montée en compétence)
/panier                             Panier
/commande                           Étape adresse du tunnel checkout
/commande/paiement                  Étape paiement (Stripe)
/commande/confirmation              Confirmation de commande
/favoris/partages/[token]           Wishlist partagée en lecture seule, sans compte (26.3)
/contact
/mentions-legales
/cgv
/confidentialite
/livraison
/retours
/login, /register                   Auth (Fortify — chemins restés en anglais, pas de miroir FR dédié)

Espace client (auth requise) :
/dashboard                          Tableau de bord client (pas /compte)
/mon-compte/commandes               Historique commandes
/mon-compte/commandes/[order]       Détail commande + suivi
/mon-compte/commandes/[order]/facture  Téléchargement facture PDF (17.1)
/mon-compte/adresses                Gestion des adresses
/mon-compte/favoris                 Wishlist (26.3)
/settings/profile, /settings/security, /settings/appearance   (pas /compte/parametres)

Pas encore implémenté (aspirationnel, ordre indicatif) :
/routines, /routines/[bundle-slug]  Bundles / routines packagées (24.2, ⚪)
/magazine, /magazine/[article-slug] Blog (18.1/18.2, ⚪)
/a-propos                           Notre histoire (jamais commencé)
/recherche?q=...                    Pas de page dédiée — recherche live intégrée à /produits (7.2)
```

## 2. Page d'accueil — structure éditoriale

1. Hero (visuel fort + CTA principal, éventuellement rotation de 2-3 mises en avant)
2. Bandeau de réassurance (livraison, paiement sécurisé, produits authentiques, retours)
3. Catégories phares (navigation visuelle, 3-6 tuiles)
4. Best-sellers (carrousel produits)
5. Mise en avant marque / nouveauté
6. Routine du moment (lien vers `/routines`)
7. Contenu éditorial (2-3 derniers articles `/magazine`)
8. Avis clients (extraits, réassurance sociale)
9. Newsletter (capture email)
10. Footer (navigation, légal, réseaux sociaux)

## 3. Fiche produit — structure

1. Fil d'Ariane (catégorie > sous-catégorie > produit)
2. Galerie image/vidéo
3. Nom, marque (lien page marque), prix (+ prix barré si promo), note moyenne (lien ancre avis)
4. Sélecteur de variante(s) (contenance / teinte selon axes définis dans `DATA_MODEL.md`)
5. Sélecteur de quantité + CTA "Ajouter au panier"
6. Barre de progression cadeau à palier si applicable
7. Bandeau réassurance courte (livraison, retour, paiement)
8. Onglets : Bénéfices / Description / Ingrédients (INCI) / Mode d'emploi / Avis
9. Produits liés / "Complète ta routine"
10. Articles liés (cross-sell éditorial, si l'article référence ce produit)

## 4. Catégorisation produit (exemple de départ, à ajuster au catalogue réel)

- **Soin visage** : Nettoyants, Toniques/Essences, Sérums, Crèmes hydratantes, Masques,
  Contour des yeux, Protection solaire
- **Soin corps** : Gommages, Laits/crèmes corps, Soins mains
- **Maquillage** : Teint, Yeux, Lèvres
- **Cheveux** : Shampoings, Soins capillaires
- **Par type de peau** : Sèche, Grasse, Mixte, Sensible, Terne (filtre transversal, pas une
  catégorie de navigation principale — utilisé en filtre catalogue, cf. `skin_types` dans
  `DATA_MODEL.md`)
- **Routines/Bundles** : Routine hydratation, Routine anti-imperfections, Découverte K-beauty

## 5. Contenu éditorial (`/magazine`)

Objectif : répondre au besoin de pédagogie du persona "Découverte" (`PRD.md`). Catégories
d'articles suggérées :

- Guides ingrédients (ex. "Le Centella Asiatica, c'est quoi ?")
- Routines pas-à-pas (ordre d'application, matin/soir)
- Guides par type de peau
- Nouveautés / focus marque

Chaque article peut lier 1 à N produits (`article_product`, cf. `DATA_MODEL.md`) pour du
cross-sell éditorial affiché en fin d'article et sur la fiche produit.

## 6. Pages légales — contenu minimum requis (FR/UE)

| Page | Contenu obligatoire |
| --- | --- |
| Mentions légales | éditeur du site, hébergeur, SIRET, directeur de publication, contact |
| CGV | prix, modalités de paiement, livraison, droit de rétractation (14 jours), garanties légales |
| Politique de confidentialité | données collectées, finalités, base légale RGPD, durée de conservation, droits utilisateur, DPO/contact |
| Politique de cookies | liste des cookies, finalités, gestion du consentement |
| Livraison | zones desservies, délais, transporteurs (Sendcloud), frais |
| Retours | procédure, délais, état du produit exigé, remboursement |

> Contenu à rédiger avec un regard juridique avant mise en ligne réelle — ce document ne
> remplace pas une validation légale, il liste seulement les sections attendues.

## 7. SEO — conventions

- URLs produit : `/produits/nom-produit-lisible` (slug via `spatie/laravel-sluggable`, cf. `STACK.md`).
- Chaque page publique a un `meta_title`/`meta_description` dédié (voir champs sur `products`
  et `articles` dans `DATA_MODEL.md`).
- Données structurées `Product` (prix, disponibilité, note) et `BreadcrumbList` sur les fiches
  produit — géré via `artesaos/seotools`.
- `sitemap.xml` généré via `spatie/laravel-sitemap`, régénéré à chaque publication de produit/article.
