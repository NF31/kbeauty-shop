# Arborescence du site & contenus

## 1. Plan du site (storefront public)

```text
/                                   Accueil
/c/[category-slug]                  Liste produits par catégorie
/c/[category-slug]/[sub-slug]       Sous-catégorie
/marque/[brand-slug]                Page marque (brand story + produits)
/p/[product-slug]                   Fiche produit
/recherche?q=...                    Résultats de recherche
/routines                           Bundles / routines packagées
/routines/[bundle-slug]             Détail d'une routine
/magazine                           Blog
/magazine/[article-slug]            Article
/a-propos                           Notre histoire
/panier                             Panier (page + drawer)
/commande                           Tunnel checkout (adresse → livraison → paiement)
/commande/confirmation/[order]      Confirmation de commande
/compte                             Tableau de bord client
/compte/commandes                   Historique commandes
/compte/commandes/[order]           Détail commande + suivi
/compte/adresses                    Gestion des adresses
/compte/favoris                     Wishlist
/compte/parametres                  Infos perso, mot de passe, 2FA (déjà scaffoldé)
/connexion, /inscription            Auth (déjà scaffoldé Fortify)
/mentions-legales
/cgv
/confidentialite
/livraison
/retours
/contact
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

- URLs produit : `/p/nom-produit-lisible` (slug via `spatie/laravel-sluggable`, cf. `STACK.md`).
- Chaque page publique a un `meta_title`/`meta_description` dédié (voir champs sur `products`
  et `articles` dans `DATA_MODEL.md`).
- Données structurées `Product` (prix, disponibilité, note) et `BreadcrumbList` sur les fiches
  produit — géré via `artesaos/seotools`.
- `sitemap.xml` généré via `spatie/laravel-sitemap`, régénéré à chaque publication de produit/article.
