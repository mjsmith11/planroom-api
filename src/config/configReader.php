<?php
	/**
	 *  Singleton location for nonsensitive configuration data
	 */
	class ConfigReader {
		private static $readDone = false;
		private static $corsOrigins;
		private static $displayErrorDetails;

		/**
		 * Gets the allowable origins for CORS requests
		 * @returns array of allowed CORS origins 
		 */
		public static function getCorsOrigins() 
		{
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
		
		private static function _read() {
			$jsonString = file_get_contents(__DIR__ . '/../../config.json');
			$config = json_decode($jsonString, true);

			self::$corsOrigins = $config['cors_origins'];
			self::$displayErrorDetails = $config['display_error_details'];

			unset($config);
			self::$readDone = true;
		}

	}