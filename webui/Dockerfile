FROM php:7.3-alpine
MAINTAINER Ahmad <ahmadt@gmail.com> https://github.com/mrahmadt/

ENV SERVER_IP="192.168.1.100"

STOPSIGNAL SIGINT

RUN	mkdir -p /var/www/html \
#&& mkdir -p /etc/dnsmasq.d \
#&& touch /etc/dnsmasq.d/smartgw.conf \
#&&	chown -R www-data:www-data /etc/dnsmasq.d \ 
&&	chown -R www-data:www-data /var/www/html

WORKDIR /var/www/html

RUN	set -x \
&&	apk add --no-cache --virtual .build-deps \
	sqlite-dev \
&&	docker-php-ext-install pdo_sqlite \
&&	apk del .build-deps

COPY code /var/www/html/
#USER	www-data

EXPOSE 8080

CMD	[ "php", "-S", "[::]:8080", "-t", "/var/www/html" ]