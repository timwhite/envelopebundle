#!/bin/bash
php app/console doctrine:schema:drop --force
php app/console doctrine:schema:create
php app/console doctrine:fixtures:load

#for f in /home/tim/Downloads/TransactionHistory*.csv
#do
#    echo $f
#    php app/console account:import "NAB Cash" "$f"
#    sleep 2;
#done
#
#for f in /home/tim/Downloads/ANZ*.csv
#do
#    echo $f
#    php app/console account:import --importANZ "ANZ Offset" "$f"
#    sleep 2;
#done

