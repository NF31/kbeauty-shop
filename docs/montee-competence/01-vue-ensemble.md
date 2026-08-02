← [Retour au sommaire](README.md)

# 1. Vue d'ensemble de l'écosystème distribué (2026)

Le système d'information est conçu selon les principes modernes de la **Persistance Polyglotte** et de l'**Architecture Orientée Événements (Event-Driven)**. Le cœur transactionnel (ventes, paniers, stocks) est isolé des calculs lourds d'Intelligence Artificielle et de la collecte de données de masse (Big Data) pour garantir une tolérance aux pannes et des performances optimales.

## 🏢 Répartition des Modules et Hébergement Cloud

*   **Boutique E-Commerce Principale** : Propulsée par **Laravel 13.20.0 / Inertia.js / React / TypeScript / Tailwind v4**. Elle gère le catalogue multi-axes, l'authentification Fortify Headless (Passkeys, 2FA), le panier via un `CartService` natif, et le tunnel d'achat Stripe.
    *   *Hébergement* : **Laravel Cloud** (Infrastructure managée native pour PHP, Vite et les workers Horizon).
*   **Interface de Diagnostic Client** : Propulsée par **Next.js 16 (App Router)**. Interface autonome et publique de capture et d'analyse cutanée.
    *   *Hébergement* : **Vercel** ou **Azure Static Web Apps**.
    *   ⚠️ **Important** : Next.js et Inertia.js ne sont **pas interopérables**. Inertia est un protocole propriétaire à un seul backend (ici Laravel) et un seul frontend SPA — il ne peut pas être « parlé » par un second frontend externe. La connexion entre les deux applications se fait donc via une **API REST/JSON classique exposée par Laravel (Sanctum)**, consommée par Next.js en Server Component, et non via Inertia.
*   **Moteur d'IA & RAG Sémantique** : Propulsé par **Java 21 / Spring Boot 3.4+ / Hibernate**. API REST asynchrone de calcul métier et de matching d'ingrédients INCI.
    *   *Hébergement* : **Azure Container Apps** (Serverless Docker à scalabilité automatique).
*   **Social Listening & Extraction Data** : Propulsé par **Python 3.12 / Scrapy Framework / Scrapy-Playwright**. Robot d'extraction industrielle de données de marché.
    *   *Hébergement* : **Azure Virtual Machine (Ubuntu Server)** pour un contrôle total sur les entrées/sorties du système de fichiers.
