---
title: Axes hors programme
group: Montée en compétence
order: 7
---

← [Retour au sommaire](README.md)

# 7. Axes hors programme (extensions libres)

> Ces sujets ne figurent pas explicitement dans le programme du Master MIAGE, mais renforcent le dossier en montrant une compréhension des enjeux réels d'un SI distribué en entreprise. À traiter en option, selon le temps disponible — voir priorisation en bas de page.

## 1. Contrat de données entre services (Schema Registry / validation de messages)

- [ ] À faire (retenu en priorité le 2026-08-07)

Aujourd'hui, RabbitMQ transporte des messages sans contrat formel entre producteur (Spring Boot) et consommateur (Laravel). Si le format d'un message change côté Spring Boot, Laravel casse silencieusement en prod.

**Proposition** : valider chaque message publié/consommé via un schéma JSON Schema (ou Avro pour aller plus loin), versionné dans un dossier `contracts/` partagé entre les deux repos.

## 2. Résilience applicative (Circuit Breaker / Retry)

- [ ] À faire (retenu en priorité le 2026-08-07)

La résilience actuelle du dossier est uniquement infra (RabbitMQ persiste les messages sur disque en cas de panne). Rien ne gère la panne applicative : si l'API IA externe timeout, Spring Boot ne fait rien de spécifique.

**Proposition** : intégrer Resilience4j côté Spring Boot (circuit breaker + retry avec backoff exponentiel) sur l'appel à l'API IA externe.

## 3. Identité centralisée (SSO / OIDC) plutôt que jeton ad-hoc

Le flux « jeton signé 2 minutes » entre Laravel et Next.js fonctionne pour 2 services mais ne scale pas si un 3ᵉ client apparaît.

**Proposition** : déployer Keycloak (self-hosted, gratuit) comme fournisseur d'identité central pour tous les services. Anticipe directement le module S3/S4 « Architecture d'entreprise à base de services ».

## 4. Observabilité distribuée (tracing de bout en bout)

Sentry capture les erreurs isolées mais pas le parcours d'une requête à travers Laravel → RabbitMQ → Spring Boot → API IA.

**Proposition** : OpenTelemetry (standard ouvert, gratuit) avec propagation d'un trace ID de bout en bout entre les services.

## 5. Infrastructure as Code (Terraform)

Le dossier actuel déploie « à la main » sur Azure Container Apps.

**Proposition** : décrire l'infra Azure (Container Apps, réseau, secrets) en Terraform, même a minima. Montre une maturité DevOps que le module « Intégration et déploiement continus » du programme ne couvre qu'en CI/CD applicatif, pas en infra.

## 6. Tests de contrat entre services (Pact ou équivalent léger)

Complète le point 1 : au lieu d'une simple validation de schéma statique, un test de contrat vérifie à chaque déploiement que Spring Boot et Laravel restent compatibles, sans dépendre d'un environnement d'intégration complet.

---

## Priorisation recommandée

| Axe | Effort | Valeur pédagogique | Priorité |
| :--- | :--- | :--- | :--- |
| 1. Contrat de données (schéma) | Faible | Élevée — comble un vrai trou du design event-driven actuel | 🔴 Haute |
| 2. Circuit Breaker / Retry | Faible | Élevée — idem | 🔴 Haute |
| 3. SSO / OIDC (Keycloak) | Moyen | Très visible en soutenance | 🟠 Moyenne |
| 4. Observabilité distribuée (OpenTelemetry) | Moyen | Très visible en soutenance | 🟠 Moyenne |
| 5. Infrastructure as Code (Terraform) | Élevé | Bonus DevOps | 🟢 Basse — si le temps le permet |
| 6. Tests de contrat (Pact) | Élevé | Bonus qualité | 🟢 Basse — si le temps le permet |
