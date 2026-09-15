# Flow

## Database

- Changer le nom d'une colonne
  - modifier la migration la contenant/ créer une migration /
  - Changer le nom dans le model
  - Changer le nom dans les scripts appelant le model (factory, seeder, controller, vues, routes)

- Créer nouvelle table
  - php artisan make:model -m nom_model
  - Modifier le nom de la table s'il faut
  - 