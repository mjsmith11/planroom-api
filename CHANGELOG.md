# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]
 - Tracking sent emails
 - Autocomplete email suggestions
 - Swagger API Documentation

## [1.1.2] - 02/14/2021
### Added
 - Honorary Dependency lkf
### Changed
 - Updates to Support PHP 7.3 and 7.4 (Test changes and an Optimization to the Invitation Process)

## [1.1.1] - 11/11/2018
### Changed
 - Use PHPMailer instead of SMTP

## [1.1.0] - 11/11/2018
### Added
 - Request Authorization Middleware
 - Endpoint for sending email invitations

## [1.0.3] - 10/28/2018
### Changed
 - Use Planroom-Authorization instead of Authorization header
 - Include root .htaccess in release

## [1.0.1] - 10/28/2018
### Added
 - .htaccess rule to redirect http to https

## [1.0.0] - 10/27/2018
### Added
 - Endpoints for authentication
 - Protection for secure routes

## [0.3.0] - 10/13/2018
### Added
 - Endpoint to get a Presigned Post to S3
 - Endpoint to get a list of plans for a job and signed download url

## [0.2.2] - 10/04/2018
### Fixed
 - Development server command for vagrant
 - Phinx migration paths, imports, and logging

## [0.2.0] - 09/27/2018
### Added
 - Logging
 - Read single job endpoint
 - Read all jobs endpoint

## [0.1.1] - 09/16/2018
### Fixed
 - Remove nonexistant template directory from build artifacts

## [0.1.0] - 09/26/2018
### Added
 - Empty PHP Slim Skeleton Project
 - CI Build using Travis
 - Phinx Database Migrations
 - MySQL Database Connectivity
 - CORS Middleware
 - Add Job Route
