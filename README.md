# planroom-api [![Master Build Status](https://travis-ci.org/mjsmith11/planroom-api.svg?branch=master)](https://travis-ci.org/mjsmith11/planroom-api)

This repository contains a php back end using the slim framework for the planroom project.
It adheres to [Semantic Versioning](https://semver.org/) and [Gitflow](https://www.atlassian.com/git/tutorials/comparing-workflows/gitflow-workflow)

## Ubuntu Environment Setup
1. Prepare an empty MySQL 5.5 database and a user with full permissions for that database. The MySQL server can be run in a docker container using `docker run --name planroom-mysql -e MYSQL_ROOT_PASSWORD=SuperSecret -d -p 3306:3306 mysql:5.5` 
1. Install php-cli7.2 `sudo apt-get install php7.2-cli`
1. Install MySQL for php 7.2 `sudo apt-get install php7.2-mysql`
1. Install php7.2-curl `sudo apt-get install php7.2-curl`
1. Install composer globally [Directions](https://getcomposer.org/doc/00-intro.md#installation-linux-unix-osx)
1. Install dependencies `composer.phar install`
1. Copy `example_config.json` to `config.json` and fill in information. Valid log levels are debug, info, notice, warning, error, critical, alert, and emergency.
1. Run database migrations `vendor/bin/phinx migrate`
1. Use `composer.phar run start` to start a development server.

## Helpful Tools
 - MySQL Workbench
 - Postman
 - [jwt.io](https://jwt.io/)

### Prepare for production
```
composer.phar install --no-dev --optimize-autoloader
```
### Run unit tests
```
composer.phar run test
```
### Run Linting
Note the db migrations directory is excluded because the phinx naming convention breaks a phpcheckstyle rule
```
composer.phar run lint
``` 

