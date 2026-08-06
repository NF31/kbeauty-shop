---
title: Charte graphique & UI
order: 7
---

# Charte graphique & UI

Référence d'inspiration : [uniikon.com](https://uniikon.com) — esthétique K-beauty premium,
minimaliste, aérée. Ce document définit les règles à suivre pour garder une cohérence visuelle
sur tout le site, implémentées avec Tailwind CSS + shadcn/ui (déjà en place dans le starter).

## 1. Principes directeurs

- **Épuré avant tout** : beaucoup de blanc/espace négatif, peu d'éléments par écran, une action
  principale claire par page.
- **Le produit est la star** : photographie produit soignée, fond neutre, mise en avant par la
  taille et l'espacement plutôt que par la décoration.
- **Douceur** : coins arrondis modérés, ombres légères, transitions fluides — jamais agressif.
- **Confiance silencieuse** : typographie fine et lisible, pas de surcharge de badges/pop-ups.

## 2. Couleurs

**Implémenté** (`resources/css/app.css`, tokens `@theme`, thèmes clair/sombre) — la proposition
initiale ci-dessous a été retenue quasiment telle quelle (rose poudré `#E8C4C4` = `--rose-300`) :

| Rôle | Valeur | Usage |
| --- | --- | --- |
| Fond principal | blanc cassé chaud | fond de page |
| Texte principal | quasi-noir | titres, corps de texte |
| Accent de marque | échelle `--rose-50` → `--rose-600` (7 teintes, pas une seule couleur plate) | secondary/accent/ring, CTA secondaires |
| Succès / stock | `--success` `#7A9B76` (vert doux) | disponibilité, confirmation |
| Badges thématiques | `--brand-sage` (sauge) / `--brand-gold` (doré) — ajoutés en cours de route, pas dans la proposition initiale | ex. badge "Vedette" sur les listings admin |

Chaque couleur a sa variante `-foreground` associée (contraste texte garanti dessus). Éviter les
couleurs vives saturées façon "promo agressive" reste la règle — le positionnement K-beauty
premium se joue sur la sobriété, pas sur le contraste criard.

## 3. Typographie

**Implémenté** (`--font-heading`/`--font-sans`, `resources/css/app.css`) :

- **Titres** : Fraunces (serif douce, ton éditorial) — l'option envisagée ci-dessous a été retenue.
- **Corps de texte** : Instrument Sans.
- Hiérarchie stricte : H1 grand et aéré (peu de mots), H2/H3 discrets, éviter plus de 3 niveaux
  de titre visibles sur une même page produit.
- Interlignage généreux (`leading-relaxed` sur les paragraphes descriptifs).

## 4. Grille & espacement

- Grille 12 colonnes, marges latérales généreuses (`px-6` mobile, `px-16`+ desktop).
- Espacement vertical entre sections d'accueil large (`py-16`/`py-24`) — laisser respirer.
- Cartes produit : ratio image carré ou 4:5, espacement uniforme, pas de bordures dures
  (préférer une ombre très légère ou aucune séparation, juste l'espace blanc).

## 5. Composants — ce qui existe déjà vs à construire

> **La quasi-totalité de cette liste est désormais construite** (voir le détail tâche par tâche
> dans `FEATURES.md`, Phases 4-7 majoritairement 🟢) — table conservée pour l'historique du
> raisonnement de départ, pas comme suivi d'avancement (c'est le rôle de `FEATURES.md`).

Le starter contient déjà les primitives Radix (`@radix-ui/react-*`) et plusieurs composants
`resources/js/components/ui` (shadcn/ui). Pas besoin de les recréer — seulement les adapter au
style et construire les composants métier storefront par-dessus.

| Besoin | Statut |
| --- | --- |
| Boutons, inputs, checkbox, dialog, tooltip, select, avatar | ✅ re-skinnés aux tokens de la charte |
| Menu de navigation | ✅ header storefront (marques, catégories — pas un mega-menu à proprement parler) |
| Notifications | ✅ `Sonner`, dédupliquées par `id` (cf. `FEATURES.md`, fix toast en double) |
| Carte produit | ✅ image, nom, marque, prix, note moyenne |
| Sélecteur de variante (contenance/teinte) | ✅ |
| Galerie produit | ✅ |
| Onglets fiche produit | ✅ Bénéfices / Description / Ingrédients (INCI) / Avis |
| Panier (drawer) | ✅ |
| Sélecteur de quantité | ✅ |
| Formulaires compte | ✅ |
| Avis clients | 🟡 Trustpilot + Klaviyo Reviews décidés (SaaS, pas de système maison) ; invitation post-achat en place, widget d'affichage encore bloqué sur l'obtention des clés API (`FEATURES.md` 14.1/14.2) |
| Barre de progression cadeau à palier | ⚪ pas construit (jamais réclamé depuis) |
| Wishlist (26.3) | ✅ ajoutée en cours de route, hors périmètre de ce document initial |

## 6. Ton éditorial

- Textes courts, orientés bénéfice ("Hydrate en profondeur" plutôt que jargon marketing lourd).
- Vocabulaire cohérent FR : "routine", "peau", "texture", "teint" — cohérent avec le persona
  "Découverte" qui a besoin de pédagogie (cf. `PRD.md`).
- CTA au singulier direct : "Ajouter au panier", "Découvrir la routine", pas de superlatifs excessifs.

## 7. Responsive & performance visuelle

- Mobile-first strict : la majorité du trafic beauté est mobile — valider chaque écran d'abord
  en vue 375px avant desktop.
- Images produit : formats modernes (WebP/AVIF via Cloudinary), lazy loading systématique hors
  above-the-fold, dimensions explicites pour éviter le layout shift.
- Micro-interactions discrètes (hover carte produit, transition ajout panier) — jamais d'animation
  qui ralentit la perception de rapidité.

## 8. Accessibilité

- Contraste texte/fond conforme AA (attention aux textes gris clair sur blanc cassé — vérifier
  le ratio, ne pas descendre sous `#6B6B6B` pour du texte informatif).
- `alt` descriptif sur toutes les images produit (nom + variante), pas juste "produit.jpg".
- Navigation clavier complète sur le sélecteur de variante, la galerie et le panier (drawer
  focustrap + fermeture Escape).

## 9. Étapes suivantes

- ~~Choisir la couleur d'accent de marque définitive et les 2 polices~~ **fait** (sections 2-3).
- ~~Page interne `/design-system`~~ **jamais construite** — les composants ont été validés
  directement dans les vraies pages storefront/admin au fil des tâches, sans détour par une
  page bac à sable.
