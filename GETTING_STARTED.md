# Getting Started

## Composer
This project uses [composer](https://getcomposer.org/) to manage dependencies. Project information including direct dependencies is stored in `composer.json`. The full dependency tree is stored in `composer.lock`. Code for installed dependencies is in the `vendor` directory.

## Slim
This project uses [Slim](https://www.slimframework.com) which is a micro framework for PHP and was created using [Slim-Skeleton](https://github.com/slimphp/Slim-Skeleton). In order for Slim to work properly, the webserver must direct all requests to `public/index.php`.

### Dependencies
Slim handles dependencies via a dependency injection container that is available to the code processing each request. This container is created in `src/dependencies.php`.

### Middleware
Slim allows middleware to be added to the application which can be executed before or after a request is processed. Middleware is added in `src/middleware.php`. This application uses middleware for adding CORS Headers, User Authentication, Request Authorization, and Request Logging.

### Routes
The routes and their handlers are defined in `src/routes.php`.

### Settings
`src/settings.php` creates an object that contains global settings for the application. These settings are stored in the dependency injection container.

## Code Structure
The following is a listing of source code directories and the responsibilities of the code in them
 - `src/config`: Reading configuration settings from `config.json`
 - `src/email` : Sending emails from the application
 - `src/jwt`   : Generating tokens used the authenticate and authorize users
 - `src/db`    : Interacting with the MySQL database
 - `src/s3`    : Interacting with the AWS S3 bucket where plan files are stored.

## Logging
This application uses [Monolog](https://github.com/Seldaek/monolog) for logging.  Logs are created using the logger in the dependency injection container. The log level is set in `config.json`. Logs are stored in files in the `logs` directory and are rotated keeping the most recent logs.

## Phinx
This project uses [phinx](https://phinx.org/) to manage database migrations. A migration is created in `src/db/migrations` each time a change to database is required. When phinx runs, it applies the appropriate migrations to the database. It creates its own table in the database to track migrations that have been applied. Phinx is configured in phinx.php.

## Unit Testing
Unit testing is done with [PHPUnit](https://phpunit.readthedocs.io/en/7.4/). The test suite is configured in `phpunit.xml`. All test classes are located in `tests/Functional` and extend `\PHPUnit_Framework_TestCase` either directly or indirectly.

## Linting
This project uses [PHPCheckstyle](http://phpcheckstyle.github.io/) to maintain consistent coding style. It is configured by command line parameters found in `composer.json`. Certain warnings are suppressed in particluar classes by using `@SuppressWarning`.

## API Documentation
The API endpoints are documented using [swagger-php](https://github.com/zircote/swagger-php) and [swagger](https://swagger.io/solutions/api-documentation/). swagger-php uses comments in the code (these always start with @OA) to generate `doc/swagger.json` which contains API documentation. Then, a docker container with swagger-ui can be run to display the data in `doc/swagger.json` in a web-based GUI.

## Travis
[Travis-CI](https://travis-ci.org) is used for [Continuous Integration Builds](https://travis-ci.org/mjsmith11/planroom-api). Build settings, scripts, etc. are in `.travis.yml`.

## Server Configuration
Configuration for an Apache server is provided using `.htaccess` files.  The `.htaccess` file in the root directory denies access to all files. It is overridden in the `public` directory with another `.htaccess` file that redirects all requests to `index.php` as reqiured by Slim.

## Docker
The file `docker-compose.yml` was added as part of the Slim Skeleton application. It's purpose is to support a docker based deployment, however, a docker based deployment has never been attempted for this project.