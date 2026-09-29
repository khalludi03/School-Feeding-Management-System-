docker pull php:8.4-apache >/dev/null 2>&1
docker run --rm php:8.4-apache ls -la /etc/apache2/mods-enabled/mpm*
