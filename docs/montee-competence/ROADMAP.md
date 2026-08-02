← [Retour au sommaire](README.md)

# Roadmap — Montée en compétence (prép. Master MIAGE)

> Séquencement réaliste du dossier technique. Objectif : livrer un jalon fonctionnel de bout en bout avant d'ouvrir le suivant, plutôt que de démarrer les 6 en parallèle et n'en finir aucun. Le projet Kbeauty en production (Laravel/Inertia) n'est pas touché — ces services se connectent en périphérie, à titre expérimental.

## Principe d'ordonnancement

1. **Fondations SI avant code** (modélisation, infra) — c'est ce que MIAGE évalue le plus, et ça structure tout le reste.
2. **Un seul microservice qui tourne réellement** avant d'en ajouter un deuxième.
3. **L'IA (diagnostic peau) en dernier** — c'est la brique la plus coûteuse/floue (budget API, prompt engineering), elle ne doit pas bloquer le reste.

---

## Phase 0 — Cadrage (J1) — ~1 semaine

- [ ] BPMN 2.0 sur Draw.io : tunnel d'achat Stripe existant + circuit asynchrone du futur diagnostic de peau
- [ ] UML : diagramme de séquence pour l'échange de jeton Laravel → Next.js, diagramme de classes JPA (même avant d'écrire le code Java — ça force à clarifier le modèle)
- [ ] Backlog Jira : User Stories du MVP « diagnostic connecté au panier », découpées en Sprints de 2 semaines

**Livrable** : dossier de conception validé, backlog priorisé. Rien codé encore.

## Phase 1 — Infra commune (J2) — ~2-3 jours

- [ ] `docker-compose.yml` (Valkey, RabbitMQ, Cassandra, SonarQube) sous WSL Ubuntu
- [ ] Vérifier chaque conteneur individuellement (`docker compose ps`, dashboards RabbitMQ `:15672`, SonarQube `:9000`)
- [ ] Premier scan SonarQube sur le repo Laravel/React existant — sert de baseline qualité, pas besoin d'attendre les microservices

**Livrable** : infra locale qui tourne, scan Sonar du projet existant fait.

## Phase 2 — Premier microservice end-to-end (J4 réduit) — ~1-2 semaines

Objectif : un Spring Boot minimal qui **lit/écrit réellement** dans Neon et **publie un message RabbitMQ** — sans IA, sans Next.js. C'est le noyau qui prouve que l'event-driven fonctionne.

- [ ] `kbeauty-ai-core-service` : squelette Spring Boot (pom.xml corrigé), connexion Neon via `application.properties`
- [ ] Une entité JPA simple (ex: `DiagnosticRequest`) + un endpoint REST `POST /diagnostics` qui l'enregistre
- [ ] `@RabbitListener` qui consomme un message de test et log le résultat (pas encore branché à un vrai producteur)
- [ ] Contrat OpenAPI via Springdoc, testé dans Swagger UI

**Livrable** : `curl POST /diagnostics` → ligne en base Neon → message publié dans RabbitMQ → consommé et loggé. Bout en bout, sans façade.

**Point de contrôle** : si cette phase prend plus de 3 semaines, il vaut mieux réduire le périmètre (ex. sauter JPA et écrire du SQL natif) que de laisser traîner.

## Phase 3 — Page diagnostic en Inertia (J5) — ~3-5 jours

> Décision (2026-08-02) : pas de pont Next.js pour cette fonctionnalité. Inertia fait déjà tourner du React moderne dans le monolithe Laravel (composants, hooks, SSR optionnel) — un pont Sanctum + Next.js séparé n'aurait ajouté que de la complexité sans bénéfice produit. Next.js est repoussé à un morceau où une vraie séparation frontend a du sens (ex. Phase 6, dashboard analytics découplé), ou à un projet dédié si l'objectif est de pratiquer Next.js pour lui-même.

- [ ] Route Laravel dédiée (`web.php`, middleware `auth` classique) qui rend une page Inertia `Diagnostic/Show`
- [ ] Contrôleur qui appelle `kbeauty-ai-core-service` (Phase 2) pour créer le `DiagnosticRequest` et passe les données en props Inertia
- [ ] Rate limiting via le middleware Laravel standard (`throttle:3,60`) sur la route — le garde-fou doit exister dès le début, même avec un diagnostic bidon
- [ ] Bouton "Ajouter au panier" sur la page qui appelle directement `CartService` (même contrôleur, pas de redirection inter-app)

**Livrable** : clic depuis la boutique → page diagnostic Inertia → panier peuplé. Le contenu du diagnostic peut être bidon (hardcodé) à ce stade — l'objectif est le flux, pas l'IA.

## Phase 4 — Data engineering (J3) — ~1-2 semaines, en parallèle possible de la phase 3

- [ ] Scrapy spider simple sur un catalogue d'ingrédients (pas encore Reddit/Playwright — commencer par du HTML statique)
- [ ] Pipeline `cassandra-driver` pour pousser les résultats dans Cassandra
- [ ] Scrapy-Playwright + social listening une fois le pipeline de base validé

**Livrable** : un spider qui tourne en cron, données visibles dans Cassandra (`cqlsh`).

## Phase 5 — Brancher l'IA réelle — ~2-3 semaines (le plus incertain, prévoir buffer)

- [ ] Choisir et budgéter l'API vision/LLM (coût par appel, quota)
- [ ] Endpoint Spring Boot qui appelle l'API IA, traite l'image en mémoire uniquement (RGPD — jamais persistée)
- [ ] Persistance des scores anonymisés uniquement (`rougeurs: 40%`)
- [ ] Brancher sur le pipeline RabbitMQ de la Phase 2

**Livrable** : diagnostic réel de bout en bout, avec coûts mesurés.

## Phase 6 — Analytics & CI/CD (J6) — ~1-2 semaines

- [ ] Export Parquet des données anonymisées, requêtes DuckDB pour 2-3 KPIs concrets
- [ ] GitHub Actions : Pest (PHP) + JUnit (Java) + scan SonarQube automatique
- [ ] Déploiement Spring Boot sur Azure Container Apps

**Livrable** : pipeline CI qui tourne sur push, dashboard KPI basique.

---

## Ce qu'on ne fait PAS en premier (pièges à éviter)

- Ne pas démarrer Cassandra + Spring Boot + Next.js + Scrapy la même semaine — c'est la garantie de finir avec 4 chantiers à moitié faits.
- Ne pas attendre d'avoir l'IA qui marche pour construire le pont Laravel↔Next.js (Phase 3) — c'est indépendant.
- Ne pas sauter la Phase 0 (modélisation) pour « aller plus vite au code » — c'est justement ce qui a de la valeur pour MIAGE, et ça évite de recoder une structure JPA mal pensée.

## Prochaine étape immédiate

Phase 0 (BPMN + UML) ou directement Phase 2 (squelette Spring Boot) si tu préfères commencer par du code concret — à toi de choisir selon ce qui te motive le plus pour démarrer.
