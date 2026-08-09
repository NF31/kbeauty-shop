# Contrats de données RabbitMQ

Copie des schémas JSON Schema définis dans `kbeauty-ai-core-service/contracts/`
(source de vérité, ce service étant le producteur des événements sur
`kbeauty.exchange` — repo séparé, pas de lien relatif possible entre les
deux). Voir `kbeauty-ai-core-service/contracts/README.md` pour la
convention de synchronisation entre les deux repos et le script de
vérification (`check-sync.sh`).

## Fichiers

- `rabbitmq/diagnostic-created.schema.json` — événement `diagnostic.created`,
  consommé ici par la commande Artisan `rabbitmq:consume-diagnostic-created`
  (`app/Console/Commands/ConsumeDiagnosticCreatedEventsCommand.php`), validé
  via `App\Infrastructure\Rabbitmq\DiagnosticCreatedEventValidator` avant
  tout traitement.
