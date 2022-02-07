FROM php:7-apache

LABEL maintainer="TRW <trw@acoby.de>" \
      org.label-schema.schema-version="1.0" \
      org.label-schema.version="1.1.0" \
      org.label-schema.name="avideo-platform" \
      org.label-schema.description="Audio Video Encoder" \
      org.label-schema.url="https://github.com/WWBN/AVideo-Encoder" \
      org.label-schema.vendor="WWBN"

ARG DEBIAN_FRONTEND=noninteractive

ENV DB_MYSQL_HOST database
ENV DB_MYSQL_PORT 3306
ENV DB_MYSQL_NAME avideo
ENV DB_MYSQL_USER avideo
ENV DB_MYSQL_PASSWORD avideo

ENV SERVER_NAME localhost
ENV ENABLE_PHPMYADMIN yes

ENV STREAMER_URL https://localhost/
ENV STREAMER_USER admin
ENV STREAMER_PASSWORD password
ENV STREAMER_URL https://localhost/
ENV STREAMER_PRIORITY 1

ENV CREATE_TLS_CERTIFICATE yes
ENV TLS_CERTIFICATE_FILE /etc/apache2/ssl/localhost.crt
ENV TLS_CERTIFICATE_KEY /etc/apache2/ssl/localhost.key
ENV CONTACT_EMAIL admin@localhost

RUN apt-get update
RUN apt-get install -y --no-install-recommends \
      git \
      zip \
      mariadb-client \
      default-libmysqlclient-dev \
      libbz2-dev \
      libmemcached-dev \
      libsasl2-dev \
      libfreetype6-dev \
      libicu-dev \
      libjpeg-dev \
      libmemcachedutil2 \
      libpng-dev \
      libxml2-dev \
      ffmpeg \
      libimage-exiftool-perl \
      curl \
      python3 \
      python3-pip \
      libzip-dev \
      libonig-dev \
      wget && \
    docker-php-ext-configure gd --with-freetype=/usr/include --with-jpeg=/usr/include && \
    docker-php-ext-install -j$(nproc) \
      bcmath \
      bz2 \
      calendar \
      exif \
      gd \
      gettext \
      iconv \
      intl \
      mbstring \
      mysqli \
      opcache \
      pdo_mysql \
      zip && \
    rm -rf \
      /tmp/* \
      /var/lib/apt/lists/* \
      /var/tmp/* \
      /root/.cache && \
    a2enmod rewrite && \
    sed -i "s|Listen 80|Listen 8000|g" /etc/apache2/ports.conf && \
    sed -i "s|:80|:8000|g" /etc/apache2/sites-available/* && \
    echo "max_execution_time = 7200\npost_max_size = 10240M\nupload_max_filesize = 10240M\nmemory_limit = 512M" >> /usr/local/etc/php/php.ini && \
    pip3 install -U youtube-dl && \
    rm -rf /var/www/html/*

COPY install /var/www/html/install
COPY model /var/www/html/model
COPY nbproject /var/www/html/nbproject
COPY objects /var/www/html/objects
COPY update /var/www/html/update
COPY view /var/www/html/view
COPY .htaccess /var/www/html
COPY CNAME /var/www/html
COPY index.php /var/www/html
COPY LICENSE /var/www/html
COPY README.md /var/www/html
COPY deploy/apache/000-default.conf /etc/apache2/sites-enabled/000-default.conf
COPY deploy/apache/phpmyadmin.conf /etc/apache2/conf-available/phpmyadmin.conf
COPY deploy/docker-entrypoint /usr/local/bin/docker-entrypoint
COPY deploy/wait-for-db.php /usr/local/bin/wait-for-db.php

RUN chown -R www-data /var/www/html && \
    install -d -m 0755 -o www-data -g www-data /var/www/html/videos

# set non-root user
USER www-data
EXPOSE 8000

VOLUME ["/var/www/html/videos"]
ENTRYPOINT ["/usr/local/bin/docker-entrypoint"]
