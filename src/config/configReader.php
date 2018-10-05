<?php
	/**
	 *  Singleton location for nonsensitive configuration data
	 */
	class ConfigReader {
		private static $readDone = false;
		private static $corsOrigins;
		private static $displayErrorDetails;
		private static $logging;
		private static $awsRegion;

		/**
		 * Gets the allowable origins for CORS requests
		 * @returns array of allowed CORS origins 
		 */
		public static function getCorsOrigins() {
			if (!self::$readDone) {
				self::_read();
			}
			return self::$corsOrigins;
		}

		/**
		 * Gets configuration for displaying of error details
		 * 
		 * @returns boolean should error details be displayed
		 */
		public static function getDisplayErrorDetails() {
			if (!self::$readDone) {
				self::_read();
			}
			return self::$displayErrorDetails;
		}

		/**
		 * Get max file setting for log rotation
		 * 
		 * @returns maximum number of log files that the rotation should allow to exist
		 */
		public static function getMaxLogFiles() {
			if (!self::$readDone) {
				self::_read();
			}
			return self::$logging['maxFiles'];
		}

		/**
		 * Get the threshold for writing to the log file
		 * 
		 * @returns Monolog constant for the current log level.
		 */
		public static function getLogLevel() {
			if (!self::$readDone) {
				self::_read();
			}
			$levels = [
				'debug'     => \Monolog\Logger::DEBUG,
				'info'      => \Monolog\Logger::INFO,
				'notice'    => \Monolog\Logger::NOTICE,
				'warning'   => \Monolog\Logger::WARNING,
				'error'     => \Monolog\Logger::ERROR,
				'critical'  => \Monolog\Logger::CRITICAL,
				'alert'     => \Monolog\Logger::ALERT,
				'emergency' => \Monolog\Logger::EMERGENCY								
			];
			return $levels[self::$logging['level']];
		}

		/**
		 * Get aws region
		 * 
		 * @returns aws region
		 */
		public static function getAwsRegion() {
			if (!self::$readDone) {
				self::_read();
			}
			return self::$awsRegion;
		}

		/**
		 * Forces the next call to reread the configuration file.
		 * This is necessary for testing.
		 * 
		 * @param container dependency container
		 */
		public static function reset($container) {
			$container['logger']->debug('Resetting Configuration');
			self::$readDone = false;
		}
		
		// This function cannot do any logging because it must be called to get configuration
		// that is needed to setup the logger.
		private static function _read() {
			$jsonString = file_get_contents(__DIR__ . '/../../config.json');
			$config = json_decode($jsonString, true);

			self::$corsOrigins = $config['cors_origins'];
			self::$displayErrorDetails = $config['display_error_details'];
			self::$logging = $config['logging'];
			self::$awsRegion = $config['aws']['region'];

			unset($config);
			self::$readDone = true;
		}

	}