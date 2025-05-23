# Migrations de la Base de Données

Ce dossier contient les scripts de migration pour la base de données SchoolManager.

## Structure des fichiers

Les fichiers de migration suivent le format : `YYYYMMDD_HHMMSS_description.sql`

Exemple : `20240315_143000_create_users_table.sql`

## Comment utiliser les migrations

1. Pour créer une nouvelle migration :
   ```bash
   php db/migrate.php create "description de la migration"
   ```

2. Pour exécuter les migrations :
   ```bash
   php db/migrate.php up
   ```

3. Pour revenir en arrière d'une migration :
   ```bash
   php db/migrate.php down
   ```

4. Pour voir le statut des migrations :
   ```bash
   php db/migrate.php status
   ```

## Bonnes pratiques

1. Chaque migration doit être idempotente (peut être exécutée plusieurs fois sans effet)
2. Inclure toujours les commandes UP et DOWN
3. Tester les migrations sur une base de données de test avant la production
4. Sauvegarder la base de données avant d'exécuter les migrations en production 