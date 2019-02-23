Testing
-------
* Mielőtt bármit próbálsz, legyen adatbázis:
`docker run -d --name localmysql -p 3306:3306 -e "DB_NAME=symfony" -e "DB_USER=symfony" -e "DB_PASS=password" sameersbn/mysql:latest`

A password kerüljön bele a config.yml-be.

`bin/console doctrine:database:create`

`bin/console doctrine:schema:create`

* Auto tesztelés:

`vendor/bin/behat`

Feature-ök helye:
`src/AppBundle/Features/*.feature`

* Fel akarod tölteni az adatbázist adatokkal?

Alap és fake adatok (admin user stb)
`bin/console h:d:f:l`

Itt lehet módosítani:
`src/AppBundle/DataFixtures/ORM/LoadUserData.php`
`src/AppBundle/DataFixtures/ORM/data.yml`

