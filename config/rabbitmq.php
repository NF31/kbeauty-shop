<?php

/*
|--------------------------------------------------------------------------
| Connexion RabbitMQ (kbeauty-ai-core-service)
|--------------------------------------------------------------------------
|
| Sans rapport avec QUEUE_CONNECTION (Valkey/Horizon, jobs internes Laravel) :
| RabbitMQ est ici le bus d'evenements inter-services avec le microservice
| Spring Boot kbeauty-ai-core-service (hors depot), conteneur Docker
| kbeauty_rabbitmq du docker-compose kbeauty-ecosystem. Voir
| docs/montee-competence/12-contrat-rabbitmq-laravel.md.
|
*/

return [
    'host' => env('RABBITMQ_HOST', 'localhost'),
    'port' => env('RABBITMQ_PORT', 5672),
    'user' => env('RABBITMQ_USER', 'guest'),
    'password' => env('RABBITMQ_PASSWORD', 'guest'),
    'vhost' => env('RABBITMQ_VHOST', '/'),

    // Doit rester identique a RabbitMQConfig.java (kbeauty-ai-core-service) :
    // ce consommateur declare l'exchange/queue/binding de facon idempotente
    // (mêmes noms, même durabilite) pour pouvoir demarrer meme si le service
    // Spring Boot n'a pas encore tourne une premiere fois.
    'exchange' => 'kbeauty.exchange',
    'queue' => 'diagnostic.created',
    'routing_key' => 'diagnostic.created',
];
