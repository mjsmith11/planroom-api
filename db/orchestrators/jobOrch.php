<?php

class JobOrch {


    public static function Create($object) {
        $pdo = self::getPdo();
        $sql = "INSERT INTO job (`name`, `bidDate`, `subcontractorBidsDue`, `prebidDateTime`, `prebidAddress`, `bidEmail`, `bonding`, `taxible`) VALUES (:name, :bidDate, :subcontractorBidsDue, :prebidDateTime, :prebidAddress, :bidEmail, :bonding, :taxible)";
        $statement = $pdo->prepare($sql);
        $statement->bindParam("name", $object['name']);
        $statement->bindParam("bidDate", $object['bidDate']);
        $statement->bindParam("subcontractorBidsDue", $object['subcontractorBidsDue']);
        $statement->bindParam("prebidDateTime", $object['prebidDateTime']);
        $statement->bindParam("prebidAddress", $object['prebidAddress']);
        $statement->bindParam("bidEmail", $object['bidEmail']);
        $statement->bindParam("bonding", $object['bonding']);
        $statement->bindParam("taxible", $object['taxible']);
        $statement->execute();
        $object['id'] = $pdo->lastInsertId();   
        return $object;
    }
}