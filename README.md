#planroom-api [![Master Build Status](https://travis-ci.org/mjsmith11/planroom-api.svg?branch=master)](https://travis-ci.org/mjsmith11/planroom-api)

This repository contains a php back end using the slim framework for the planroom project.
It adheres to [Semantic Versioning](https://semver.org/) and [Gitflow](https://www.atlassian.com/git/tutorials/comparing-workflows/gitflow-workflow)

## Ubuntu Environment Setup
1. Install php-cli7.2 `sudo apt-get install php7.2-cli`
1. Install composer globally [Directions](https://getcomposer.org/doc/00-intro.md#installation-linux-unix-osx)

## Project Setup
```
composer.phar install
```
### Serve for development
```
composer.phar start
```
### Prepare for production
```
composer.phar install --no-dev --optimize-autoloader
```
### Run unit tests
```
composer.phar test
```

