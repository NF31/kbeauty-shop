← [Retour au sommaire](README.md)

# 6. Manuel d'installation des composants (WSL Ubuntu)

## 📂 A. Infrastructure Commune : `docker-compose.yml`

```yaml
version: '3.8'

services:
  cassandra:
    image: cassandra:latest
    container_name: kbeauty_cassandra
    ports:
      - "9042:9042"
    volumes:
      - cassandra_data:/var/lib/cassandra
    environment:
      - CASSANDRA_CLUSTER_NAME=KBeautyBigDataCluster
    networks:
      - kbeauty_network

  rabbitmq:
    image: rabbitmq:3-management-alpine
    container_name: kbeauty_rabbitmq
    ports:
      - "5672:5672"   # Port AMQP
      - "15672:15672" # Dashboard Web (guest / guest)
    volumes:
      - rabbitmq_data:/var/lib/rabbitmq
    networks:
      - kbeauty_network

  valkey:
    image: bitnami/valkey:latest
    container_name: kbeauty_valkey
    ports:
      - "6379:6379" # Port compatible avec la configuration Redis de Laravel Horizon
    volumes:
      - valkey_data:/bitnami/valkey/data
    networks:
      - kbeauty_network

  sonarqube:
    image: sonarqube:community
    container_name: kbeauty_sonarqube
    ports:
      - "9000:9000" # Interface d'analyse de qualité de code
    volumes:
      - sonarqube_data:/opt/sonarqube/data
      - sonarqube_extensions:/opt/sonarqube/extensions
    networks:
      - kbeauty_network

volumes:
  cassandra_data:
  rabbitmq_data:
  valkey_data:
  sonarqube_data:
  sonarqube_extensions:

networks:
  kbeauty_network:
    driver: bridge
```

*Commandes d'exécution dans votre terminal Ubuntu :*
```bash
docker compose up -d
docker compose ps
```

## 📁 B. Configuration du Micro-service Java Spring Boot (`pom.xml`)

Créez le répertoire `kbeauty-ai-core-service` sur votre WSL et injectez les dépendances pour Hibernate, OpenAPI et RabbitMQ :

```xml
<?xml version="1.0" encoding="UTF-8"?>
<project xmlns="http://maven.apache.org/POM/4.0.0"
         xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:schemaLocation="http://maven.apache.org/POM/4.0.0 http://maven.apache.org/xsd/maven-4.0.0.xsd">
    <modelVersion>4.0.0</modelVersion>
    <parent>
        <groupId>org.springframework.boot</groupId>
        <artifactId>spring-boot-starter-parent</artifactId>
        <version>3.4.1</version>
        <relativePath/>
    </parent>
    <groupId>com.kbeauty</groupId>
    <artifactId>ai-core-service</artifactId>
    <version>1.0.0</version>
    <name>ai-core-service</name>
    <description>Moteur IA sémantique et RAG pour boutique e-commerce</description>
    <properties>
        <java.version>21</java.version>
    </properties>
    <dependencies>
        <dependency>
            <groupId>org.springframework.boot</groupId>
            <artifactId>spring-boot-starter-web</artifactId>
        </dependency>
        <dependency>
            <groupId>org.springdoc</groupId>
            <artifactId>springdoc-openapi-starter-webmvc-ui</artifactId>
            <version>2.6.0</version>
        </dependency>
        <dependency>
            <groupId>org.springframework.boot</groupId>
            <artifactId>spring-boot-starter-data-jpa</artifactId>
        </dependency>
        <dependency>
            <groupId>org.postgresql</groupId>
            <artifactId>postgresql</artifactId>
        </dependency>
        <dependency>
            <groupId>org.springframework.boot</groupId>
            <artifactId>spring-boot-starter-amqp</artifactId>
        </dependency>
        <dependency>
            <groupId>org.projectlombok</groupId>
            <artifactId>lombok</artifactId>
            <scope>provided</scope>
        </dependency>
    </dependencies>
</project>
```

*Compilation initiale sous Linux :*
```bash
mkdir -p src/main/java/com/kbeauty/aicore src/main/resources
mvn clean compile
```

## 📁 C. Configuration du Robot Scrapy Python (`requirements.txt`)

Créez le répertoire `kbeauty-data-scraper` sur votre WSL Ubuntu, initialisez son environnement virtuel (`.venv`) et installez la stack :

```bash
python3 -m venv .venv
source .venv/bin/activate
cat <<'EOT' > requirements.txt
scrapy>=2.11.0
scrapy-playwright>=0.4
cassandra-driver>=3.29
pika>=1.3.2
python-dotenv>=1.0.1
EOT
pip install --upgrade pip
pip install -r requirements.txt
scrapy startproject core_scraper .
```

## 📁 D. Configuration de l'Interface Next.js 16

```bash
npx create-next-app@latest kbeauty-diagnostic-frontend --typescript --tailwind --eslint --src-dir --app
cd kbeauty-diagnostic-frontend
npm install zustand zod react-hook-form @radix-ui/react-tabs @sentry/nextjs
```
