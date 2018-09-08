<?php
    // Singleton location for nonsensitive configuration data
    class ConfigReader {
        private static $readDone = false;
        private static $corsOrigins;
        private static $displayErrorDetails;

        public static function getCorsOrigins() {
            if (!self::$readDone) {
                self::read();
            }
            return self::$corsOrigins;
        }

        public static function getDisplayErrorDetails() {
            if (!self::$readDone) {
                self::read();
            }
            return self::$displayErrorDetails;
        }
        
        private static function read() {
            $json_string = file_get_contents(__DIR__ . '/../../config.json');
            $config = json_decode($json_string, true);

            self::$corsOrigins = $config['cors_origins'];
            self::$displayErrorDetails = $config['display_error_details'];

            unset($config);
            self::$readDone = true;
        }

    }