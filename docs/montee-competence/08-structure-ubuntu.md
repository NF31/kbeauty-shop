← [Retour au sommaire](README.md)

# 8. Structure de dossiers sous WSL Ubuntu

> Tous les services (hors boutique Laravel/Inertia, qui reste sur Windows/le repo actuel) tournent depuis WSL Ubuntu pour éviter la couche de traduction Docker Desktop↔WSL2 et coller à l'environnement de production (Azure Container Apps et la VM de scraping sont Linux).

## Arborescence recommandée

```
~/projects/kbeauty-ecosystem/
├── docker-compose.yml              # Infra commune : Cassandra, RabbitMQ, Valkey, SonarQube
├── .env                             # Variables partagées (jamais commit)
├── README.md                        # Point d'entrée : comment lancer l'écosystème complet
│
├── kbeauty-ai-core-service/         # Microservice Java Spring Boot
│   ├── src/
│   │   └── main/
│   │       ├── java/com/kbeauty/aicore/
│   │       │   ├── controller/      # Endpoints REST
│   │       │   ├── service/         # Logique métier (RAG, matching INCI)
│   │       │   ├── repository/      # Accès JPA/Hibernate vers Neon
│   │       │   ├── entity/          # Entités JPA
│   │       │   ├── amqp/            # Listeners/Publishers RabbitMQ
│   │       │   └── config/          # Config Spring (DB, AMQP, OpenAPI)
│   │       └── resources/
│   │           └── application.properties
│   ├── src/test/java/               # Tests JUnit
│   ├── pom.xml
│   └── Dockerfile
│
├── kbeauty-data-scraper/            # Robot Python Scrapy
│   ├── .venv/                       # (non versionné)
│   ├── core_scraper/
│   │   ├── spiders/                 # Un fichier par source (ingrédients, reddit...)
│   │   ├── pipelines.py             # Pipeline vers Cassandra
│   │   ├── items.py
│   │   └── settings.py
│   ├── requirements.txt
│   └── scrapy.cfg
│
├── kbeauty-diagnostic-frontend/     # Next.js 16
│   ├── src/
│   │   ├── app/
│   │   │   ├── diagnostic/          # Route principale du diagnostic
│   │   │   └── api/                 # Server Actions / routes internes
│   │   ├── components/
│   │   └── lib/                     # Client API vers Laravel + Spring Boot
│   ├── package.json
│   └── next.config.ts
│
├── contracts/                       # Schémas JSON partagés (voir axe hors-programme #1)
│   └── diagnostic-request.schema.json
│
├── infra/                           # Si Terraform ajouté plus tard (axe hors-programme #5)
│   └── azure/
│
└── docs/                            # Copie ou lien symbolique vers docs/montee-competence du repo Kbeauty
```

## Pourquoi cette structure

- **Un dossier racine par écosystème** (`kbeauty-ecosystem/`) plutôt que d'éparpiller les services dans `~/` — un seul `docker-compose.yml` à la racine orchestre tout, chaque service garde son propre `Dockerfile`.
- **Le repo Laravel/Inertia (Kbeauty) reste séparé**, sur Windows ou son propre clone WSL — ce n'est pas un microservice de cet écosystème, c'est le système existant auquel on se connecte.
- **`contracts/` à la racine** et pas dans un des services : un schéma partagé entre producteur (Spring Boot) et consommateur (Laravel) ne doit appartenir à aucun des deux exclusivement.

## Commandes de mise en place

```bash
mkdir -p ~/projects/kbeauty-ecosystem/{contracts,infra/azure}
cd ~/projects/kbeauty-ecosystem

# Microservice Java
mkdir -p kbeauty-ai-core-service/src/main/java/com/kbeauty/aicore/{controller,service,repository,entity,amqp,config}
mkdir -p kbeauty-ai-core-service/src/main/resources
mkdir -p kbeauty-ai-core-service/src/test/java

# Scraper Python
mkdir -p kbeauty-data-scraper/core_scraper/spiders

# Frontend Next.js (généré via create-next-app, voir 06-installation.md)
```
