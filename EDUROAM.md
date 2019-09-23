# samlidp and eduroam

See also https://wiki.ubuntunet.net/display/EDUID/Enable+samlidp+as+eduroam+IdP+backend

samlidp can easily be enhanced to act as a backend for eduroam authentication requests.

These instructions have been tested with PostgreSQL.

## Create additional tables for FreeRADIUS SQL module
A script with the SQL commands to create the necessary table for FreeRADIUS v3 can be found under conf/freeradius/schema.sql
The only change in this schema.sql compared to the original one supplied with FreeRADIUS is that `radcheck` is not a table but a view onto `idp_internal_mysql_user`.

Run the script in a web ui (eg. pgAdmin4) or on the CLI. Make sure that the tables and the view belong to the same user as the tables for samlidp.

If you have the database as a Docker container in the same docker compose file, you might just run this command:
`docker-compose exec <CONTAINER_NAME> psql -f /tmp/radius-schema.sql <DATABASE_NAME> <DATABASE_USER>`
