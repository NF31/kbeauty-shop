# Roadmap

Phasage basé sur `FEATURES.md` (phases 1-11). Objectif : livrer un tunnel d'achat complet et
vendable le plus tôt possible (fin de Phase 3), puis enrichir progressivement.

## Jalon 1 — MVP vendable (Phases 1 à 3, priorité P0)

Fondations, catalogue produit, panier, checkout, paiement Stripe. Livrable : un client peut
parcourir le catalogue, ajouter au panier et payer une commande réelle.

**Sortie de ce jalon** : Phases 4-5 (livraison, emails transactionnels) et Phase 9 (pages légales)
doivent être terminées en parallèle avant toute mise en ligne publique — un site marchand ne peut
pas être lancé sans CGV, RGPD, ni email de confirmation de commande.

## Jalon 2 — Lancement public

Phases 4, 5, 9 complètes (P0) + Phase 7 (admin Filament, gestion commandes) pour pouvoir opérer
le site au quotidien sans passer par la base de données directement.

## Jalon 3 — Post-lancement rapide (V1.1)

Éléments P1 des phases déjà en place : Apple Pay/Google Pay, filtres catalogue, avis clients
(Phase 6), sitemap/SEO (Phase 8), relance panier abandonné, mega-menu, bundles (Phase 11 - P1),
tests Pest sur le domaine e-commerce (Phase 10).

## Jalon 4 — Croissance (V2)

Éléments P2 : coupons, cadeaux à paliers, blog (Phase 8), fidélisation/parrainage/gift cards
(Phase 11), recherche Meilisearch si le catalogue le justifie, monitoring Sentry généralisé.

## Jalon 5 — Ambitions long terme (V3, P3)

À ne considérer qu'une fois le site a du trafic et des clients récurrents réels : multi-devise,
réachat/abonnement récurrent (voir note dans `FEATURES.md` sur pourquoi ces éléments sont
volontairement repoussés).

## Principe de séquencement

- Ne jamais commencer une feature P1/P2 tant qu'une feature P0 du même jalon n'est pas terminée.
- Les deux modules identifiés comme les plus coûteux en temps réel (avis clients, cadeaux à
  paliers — voir `FEATURES.md`) sont volontairement placés après le MVP : ils ne bloquent pas la
  capacité à vendre, contrairement au catalogue/panier/paiement.
- Revoir ce document après chaque jalon atteint pour réajuster les priorités selon les retours
  clients réels plutôt que de suivre le plan initial à l'aveugle.
