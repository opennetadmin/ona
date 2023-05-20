# This Dockerfile is intended for development and testing purposes only. Not
# recommended for production use.
#
# First: Build an image with a specific version of Ubuntu and tag it as such.
#    docker build --build-arg UBUNTU_VERSION=23.04 -t ona-dev:23.04 .
#
# Second: Start the container for general use. Point your browser to http://localhost/ona
#    docker run -p 80:80 -it ona-dev:23.04
#
# OR
#
# Start the container for development. Mount the current directory as a volume
# This will allow you to edit the files on your host and have them be hosted
# in the container
#    docker run -p 80:80 -it -v $(pwd):/opt/ona ona-dev:23.04
#
# This assumes you are in the directory you cloned the ONA repo into.
# Also, if you have already installed this prior, you may need to remove
# www/local/config/database_settings.conf.php to get the install to run again.

ARG UBUNTU_VERSION=latest
FROM ubuntu:${UBUNTU_VERSION}

LABEL description="OpenNetAdmin development and testing docker image"
LABEL Author="Matt Pascoe <matt@opennetadmin.com>"

ENV TZ=UTC
RUN ln -snf /usr/share/zoneinfo/$TZ /etc/localtime && echo $TZ > /etc/timezone

RUN apt-get update
RUN apt-get -y install git mariadb-server apache2 php-gmp php-mysql libapache2-mod-php php-mbstring php-xml composer unzip && \
    apt-get clean && \
    rm -rf /var/lib/apt/lists/*

RUN git -C /opt clone https://github.com/opennetadmin/ona.git -b libupgrades

ENV APACHE_RUN_USER www-data
ENV APACHE_RUN_GROUP www-data
ENV APACHE_LOG_DIR /var/log/apache2

RUN ln -sf /opt/ona/www /var/www/html/ona
RUN rm -f /var/www/html/index.html

RUN touch /var/log/ona.log
RUN chmod 666 /var/log/ona.log

RUN chown www-data /opt/ona/www/local/config

# Start as mariadb or mysql depending on version of Ubuntu.
RUN service mariadb start || service mysql start

RUN echo "\n\n\n"|php /opt/ona/install/installcli.php

EXPOSE 80

# Since we are running mysql and apache, start them both. Also runs initial install.
CMD bash -c 'service mariadb start || service mysql start && echo "\n\n\n"|php /opt/ona/install/installcli.php && /usr/sbin/apache2ctl -D FOREGROUND'
