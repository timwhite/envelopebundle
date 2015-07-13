
php app/console doctrine:schema:drop --force
php app/console doctrine:schema:create
php app/console doctrine:fixtures:load
php app/console account:import "$1" "$2"
