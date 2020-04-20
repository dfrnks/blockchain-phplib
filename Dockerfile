FROM php:7.2-cli

COPY . /srv/app

RUN docker-php-ext-install pcntl

WORKDIR /srv/app
CMD [ "php", "service", "start", "80", "2200"]

#sudo docker build --file Dockerfile -t mam-docker .

#sudo docker run --rm -p 8080:80 mam-docker

#sudo docker run --rm -p 8080:80 -p 2346:2346 mam-docker