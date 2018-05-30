project=umarket-wms
service=nginx-php-fpm
dir=$(shell pwd)

start:
	@docker-compose up -d --build

stop:
	@docker-compose down

restart: stop start

ssh:
	@docker-compose exec $(service) sh
