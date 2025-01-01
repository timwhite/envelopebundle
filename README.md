EnvelopeBudget
==============

A budgeting app based on the Envelope Budgeting method, using virtual envelopes (that unfortunately can be put into the negative)

## Setup production
- Clone the git repo `git clone https://github.com/timwhite/envelopebundle.git && cd envelopebundle`
- Install packages `composer install`
- Create `.env.local` with the following variables filled out
  ```config
  HWI_GOOGLE_CLIENT_ID=
  HWI_GOOGLE_CLIENT_SECRET=
  DATABASE_URL=
  APP_ENV=prod`
   ```
- If migrating from older version 3.4, see below for doctrine migration commands
- Setup assets and run migrations
  ```console
  composer install --no-dev --optimize-autoloader
  composer dump-env prod
  APP_ENV=prod APP_DEBUG=0 php ./bin/console cache:clear
  APP_ENV=prod APP_DEBUG=0 php ./bin/console cache:warmup
  php ./bin/console assets:install
  php ./bin/console importmap:install
  php ./bin/console asset-map:compile
  php ./bin/console doctrine:migrations:migrate
  php ./bin/console train:budget
  ```  


## Bootstrap development
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
  INSERT INTO doctrine_migration_versions (version, executed_at, execution_time) SELECT concat("DoctrineMigrations\\Version", version), NULL, 1 FROM migration_versions;
  ```

## Budget category suggestion
Make sure you regularly train with
```console
docker compose -f docker-compose.dev.yml exec php /code/bin/console -vvv train:budget
```