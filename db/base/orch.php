<?php
    class BaseOrch {
        private static $pdo;

        private static function getPdo() {
            if (!isset(self::$pdo)) {
                $dbSettings = require __DIR__ . '/../connection.php';
                self::$pdo = $dbSettings['conn'];
            }
            return self::$pdo;
        }
    }