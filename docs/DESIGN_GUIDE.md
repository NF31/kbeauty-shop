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

Palette à affiner avec une identité de marque réelle, mais structure recommandée :

| Rôle | Exemple | Usage |
| --- | --- | --- |
| Fond principal | `#FFFFFF` / `#FAF9F7` (blanc cassé chaud) | fond de page |
| Texte principal | `#1A1A1A` | titres, corps de texte |
| Texte secondaire | `#6B6B6B` | descriptions, métadonnées |
| Accent de marque | 1 couleur pastel signature (ex. rose poudré `#E8C4C4`, sauge `#B7C4B0`, ou beige `#D9C7B8`) | CTA secondaires, liens actifs, highlights |
| CTA principal | couleur foncée neutre (`#1A1A1A`) ou l'accent en version saturée | boutons "Ajouter au panier", "Commander" |
| Succès / stock | vert doux `#7A9B76` | disponibilité, confirmation |
| Erreur | rouge doux `#C97064` (pas un rouge vif agressif) | erreurs formulaire |

Éviter les couleurs vives saturées façon "promo agressive" — le positionnement K-beauty premium
se joue sur la sobriété, pas sur le contraste criard.

Implémentation : définir ces tokens comme variables Tailwind (`@theme` dans `resources/css`),
pas de couleurs codées en dur dans les composants.

## 3. Typographie

- **Titres** : une police serif fine ou sans-serif élégante avec bonne hauteur de x (ex. style
  Söhne, Public Sans, ou une serif douce type Fraunces/Playfair pour un ton plus éditorial).
- **Corps de texte** : sans-serif lisible (Inter, Public Sans).
- Hiérarchie stricte : H1 grand et aéré (peu de mots), H2/H3 discrets, éviter plus de 3 niveaux
  de titre visibles sur une même page produit.
- Interlignage généreux (`leading-relaxed` sur les paragraphes descriptifs).

## 4. Grille & espacement

- Grille 12 colonnes, marges latérales généreuses (`px-6` mobile, `px-16`+ desktop).
- Espacement vertical entre sections d'accueil large (`py-16`/`py-24`) — laisser respirer.
- Cartes produit : ratio image carré ou 4:5, espacement uniforme, pas de bordures dures
  (préférer une ombre très légère ou aucune séparation, juste l'espace blanc).

## 5. Composants — ce qui existe déjà vs à construire

Le starter contient déjà les primitives Radix (`@radix-ui/react-*`) et plusieurs composants
`resources/js/components/ui` (shadcn/ui). Pas besoin de les recréer — seulement les adapter au
style et construire les composants métier storefront par-dessus.

| Besoin | Déjà présent | À construire |
| --- | --- | --- |
| Boutons, inputs, checkbox, dialog, tooltip, select, avatar | oui (shadcn/ui) | juste re-skinner aux couleurs/rayons de la charte |
| Menu de navigation | `NavigationMenu` | transformer en mega-menu catégories |
| Notifications | `Sonner` | messages ajout panier / erreurs formulaire |
| Carte produit | — | image, nom, marque, prix (+ prix barré), note moyenne |
| Sélecteur de variante (contenance/teinte) | `ToggleGroup` (base) | pastilles arrondies, état sélectionné = accent marque |
| Galerie produit | `Dialog` (base pour le zoom) | miniatures verticales desktop, carrousel swipe mobile |
| Onglets fiche produit | — (pas de `Tabs` Radix installé) | Bénéfices / Description / Ingrédients (INCI) / Avis — ajouter `@radix-ui/react-tabs` |
| Panier (drawer) | `Dialog` (base) | mini-panier accessible depuis header |
| Sélecteur de quantité | — | composant simple `-` / input / `+` |
| Formulaires compte | `Label`, `Checkbox` | `Input`, validation Zod + React Hook Form (voir `STACK.md`) |
| Avis clients | — | étoiles (icône `Star` de `lucide-react`, déjà installé) + liste + formulaire |
| Barre de progression cadeau à palier | — | ex. "Plus que 12€ pour recevoir votre cadeau" |

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

- Choisir la couleur d'accent de marque définitive et les 2 polices (titre/corps) avant de
  configurer les tokens Tailwind (`@theme`) — évite de tout retoucher plus tard.
- Construire une page interne `/design-system` pour valider les composants storefront avant
  intégration dans les vraies pages.
