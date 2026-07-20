# Roadmap

Phasage basé sur `FEATURES.md` (Phases 1-11, numérotation `#.#` = section.tâche). Objectif : livrer
un tunnel d'achat complet et vendable le plus tôt possible (fin de Phase 3), puis enrichir
progressivement.

## Jalon 1 — MVP vendable (Phases 1 à 3, priorité P0)

Fondations (1-3), catalogue produit (4-7), panier/checkout/paiement Stripe (8-9, hors 9.3/9.5 qui
sont P1). Livrable : un client peut parcourir le catalogue, ajouter au panier et payer une commande
réelle.

**Sortie de ce jalon** : les items **P0 et P1** des Phases 4-5 (livraison, emails transactionnels)
et tous les items P0 de la Phase 9 (pages légales) doivent être terminés en parallèle avant toute
mise en ligne publique — un site marchand ne peut pas être lancé sans CGV, RGPD, ni email de
confirmation de commande. Contrairement aux autres jalons, ici la contrainte n'est pas "P0
uniquement" : la Phase 4 (livraison) n'a aucun item P0 dans `FEATURES.md` (11.1/11.2 sont P1) mais
reste indispensable pour livrer une vraie commande.

## Jalon 2 — Lancement public

Phase 4 (livraison, 11.1-11.2, P1), Phase 5 complète (12-13), Phase 9 complète (20.1-20.6, P0) +
Phase 7 P0 (16.1-16.3, admin Filament : dashboard, CRUD produits, gestion commandes) pour pouvoir
opérer le site au quotidien sans passer par la base de données directement. Monitoring Sentry
(22.1, P1) à activer avant l'ouverture publique — pas en Jalon 4, contrairement à une lecture
rapide des priorités.

## Jalon 3 — Post-lancement rapide (V1.1)

Tous les items **P1** restants : prix barré + slugs SEO + recherche + filtres catalogue (6.4, 7.1,
7.2, 7.3), Apple Pay/Google Pay (9.3), historique commandes (9.5), génération étiquettes déjà
couvertes en Jalon 2 (11.2), email expédition + queues Horizon (12.3, 13.1), avis clients (14-15,
Phase 6 est P1 dans son ensemble), sitemap/SEO (19.1-19.2), relance panier abandonné (24.1),
bundles (24.2), mega-menu (24.3), pixels marketing (27.1), facturation PDF (17.1), debug local
(22.2), tests Pest sur le domaine e-commerce (21.1).

## Jalon 4 — Croissance (V2)

Éléments **P2** : coupons (10.1), cadeaux à paliers (10.2), Q&A produit (15.4), export CSV + KPIs
admin (17.2-17.3), blog (18.1-18.2), guide de choix produit (6.5), alertes stock bas (4.5), recherche
Meilisearch si le catalogue le justifie (7.2 passe de la recherche SQL basique à Meilisearch),
fidélisation/parrainage/gift cards/recommandations (23.1-23.3, 24.4), multi-langue (25.1), support
client + portail retours + wishlist partageable (26.1-26.3), permissions admin fines (27.2).

## Jalon 5 — Ambitions long terme (V3, P3)

À ne considérer qu'une fois le site a du trafic et des clients récurrents réels : multi-devise
(25.2), réachat/abonnement récurrent (26.4) (voir note dans `FEATURES.md` sur pourquoi ces éléments
sont volontairement repoussés).

## Principe de séquencement

- Ne jamais commencer une feature P1/P2 tant qu'une feature P0 du même jalon n'est pas terminée.
- Les deux modules identifiés comme les plus coûteux en temps réel (avis clients 14-15, cadeaux à
  paliers 10.2 — voir `FEATURES.md`) sont volontairement placés après le MVP : ils ne bloquent pas
  la capacité à vendre, contrairement au catalogue/panier/paiement.
- Revoir ce document après chaque jalon atteint pour réajuster les priorités selon les retours
  clients réels plutôt que de suivre le plan initial à l'aveugle.
