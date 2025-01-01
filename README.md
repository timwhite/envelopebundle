EnvelopeBudget
==============

A budgeting app based on the Envelope Budgeting method, using virtual envelopes (that unfortunately can be put into the negative)


## Bootstrap
- Setup .env.local with `HWI_GOOGLE_CLIENT_SECRET` and `DATABASE_URL` if needed.
- docker compose -f docker-compose.dev.yml up -d
- docker compose -f docker-compose.dev.yml exec php /code/bin/console assets:install
- docker compose -f docker-compose.dev.yml exec php /code/bin/console importmap:install
- docker compose -f docker-compose.dev.yml exec php /code/bin/console doctrine:migrations:migrate
- Open http://127.0.0.1:8000/

## Upgrading from Symfony 3.4 to Symfony 6.4 version
- Create new migrations table `docker compose -f docker-compose.dev.yml exec php /code/bin/console doctrine:migrations:sync-metadata-storage`
- Move existing migrations to new table
  ```sql
  INSERT INTO doctrine_migration_versions (version, executed_at, execution_time) SELECT concat("DoctrineMigrations\\Version", version), executed_at, 1 FROM migration_versions;
  ```

## Budget category suggestion
Make sure you regularly train with
```console
docker compose -f docker-compose.dev.yml exec php /code/bin/console -vvv train:budget
```