# colibri-locations-api

API for Italian locations focusing on Eurostat codes (NUTS, etc.).

## Endpoints

First of all, you need to pass a GET parameter called "access_token", with the value of "BN78FGH". This is just so if the API ever gets abused we can change it to disable access.

### GET /provinces

Returns every province in Italy.

Example;

```
http://locations-api.dev.progettieducativi.it/api/v1/provinces?access_token=BN78FGH
```

### GET /cities

Returns every city in Italy.

Example;

```
http://locations-api.dev.progettieducativi.it/api/v1/cities?access_token=BN78FGH
```

## Run locally

You can quickly run a server without a database in the current directory with:

```
sudo docker run -p 8777:80 -v $(pwd):/var/www/html/ ipeos/lamp-dev:latest
```

Get the Docker image with:

```
sudo docker pull ipeos/lamp-dev
```

NOTE: you shouldn't run Docker as root, but this is the default setup.

You can update the port (change `8777` to whatever you want). `$(pwd)` should mean the current directories in most shells, otherwise specify absolute path.

Some vars can upgrade the php.ini

PHP_ERROR_REPORTING: E_ALL & ~E_DEPRECATED & ~E_STRICT
PHP_DISPLAY_ERRORS: On
PHP_UPLOAD_MAX_FILE_SIZE: 20M
PHP_POST_MAX_SIZE: 28M
PHP_MEMORY_LIMIT: 256M
PHP_EXPOSE_PHP: Off
PHP_TIMEZONE: UTC

For more info, see: https://github.com/ipeos-and-co/docker-lamp-dev/