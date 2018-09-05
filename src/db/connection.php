<?php
    class Connection {
        private static $connection;

        public static function getConnection() {
            if (!isset(self::$connection)) {
        
                $json_string = file_get_contents(__DIR__ . '/../config.json');
                $config = json_decode($json_string, true);
                
                $host    = $config['mysql']['host'];
                $port    = $config['mysql']['port'];
                $db      = $config['mysql']['database'];
                $user    = $config['mysql']['username'];
                $pass    = $config['mysql']['password'];
                $charset = 'utf8';

                $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
                $opt = [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ];
                try {
                    $pdo =  new PDO($dsn, $user, $pass, $opt);
                    unset($host, $port, $user, $pass, $charset, $dsn, $config, $opt);
                    self::$connection = [
                        'conn' => $pdo,
                        'dbName' => $db
                    ];
                } catch (\PDOException $e) {
                    unset($host, $port, $db, $user, $pass, $charset, $dsn, $config, $opt);
                    throw new \PDOException($e->getMessage(), (int)$e->getCode());
                }
            }
            return self::$connection;
        }
    }