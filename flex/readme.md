Preconditions:
1. You already have downloaded https://gitlab.com/umarket/docker-nginx-php-fpm-7.2
2. You already have downloaded https://gitlab.com/umarket/docker-postgres-96-alpine
3. External network must be created before `make start` with `docker network create -d bridge --attachable umarket_network`

Project installation:
1. Install docker from https://www.docker.com/community-edition#/download .
2. Install Make helper from http://gnuwin32.sourceforge.net/packages/make.htm (direct donload link http://gnuwin32.sourceforge.net/downlinks/make.php ) and add "c:\Program Files (x86)\GnuWin32\bin\" to system Path varaiable.
3. Open windows console as Administartor. Go to repository dir like `cd d:/wms/flex/`.
4. Configure docker-compose.yml file with own paths for contexts in `postgres` and `nginx-php-fpm` services.
5. Create volume to store PG data between restarts `docker volume create wms_pgdata`.
6. Start container using make helper `make start`. It will build and start Postgres and Nginx-php container.
7. Go to container ssh using `make ssh`. And copy config `cp d:/wms/flex/config/packages/valpio_constants.yaml.prod d:/wms/flex/config/packages/valpio_constants.yaml` and edit it if needed.
8. Run `composer install` from ssh.
9. In your .env file set DB connection config. You can use `DATABASE_HOST=postgres` for automatic IP resolving
10. Now you can import database. Postgres is accessible via 127.0.0.1:65431 (port is configured in d:/wms/flex/docker-compose.yml). For connecting to Postgres you can use for example https://www.heidisql.com/download.php .
11. Run `php bin/console doctrine:schema:update --force` from ssh.

12. Now your site must be enable under `http://192.168.0.13:8051/api/doc` .


HINTS:
- for debugging you need to specify your host IP in d:/wms/flex/docker-compose.yml file under environment: like - `XDEBUG_CONFIG=remote_host=192.168.0.13`

TODO:
- write unit tests