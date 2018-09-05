<?php
    require_once(__DIR__ . "/../connection.php");
    abstract class BaseOrch {
        protected static $tableName;
        protected static $fieldList;

        public static function Create($object) {
            if (isset($object['id'])) {
                throw new Exception("Id cannot be specified on Create");
                
            }

            $pdo = Connection::getConnection()['conn'];
            $fields = "";
            $valuePlaceholders = "";

            foreach (static::$fieldList as $value) {
                $fields = $fields . "`" . $value . "`, ";
                $valuePlaceholders = $valuePlaceholders . ":" . $value . ", ";
            }
            // remove trailing comma and space
            $fields = substr($fields, 0, -2);
            $valuePlaceholders = substr($valuePlaceholders, 0, -2);

            $sql = "INSERT INTO " . static::$tableName . " (" . $fields . ") VALUES (" . $valuePlaceholders . ")";
            $statement = $pdo->prepare($sql);

            foreach (static::$fieldList as $value) {
                $statement->bindParam($value, $object[$value]);
            }
            $statement->execute();
            return self::Read($pdo->lastInsertId());
        }

        public static function Read($id) {
            $pdo = Connection::getConnection()['conn'];
            $sql = "SELECT * FROM " . static::$tableName . " WHERE `id` = :id";
            $statement = $pdo->prepare($sql);
            $statement->bindParam("id", $id);
            $statement->execute();
            return $statement->fetch(PDO::FETCH_ASSOC);

        }
    }