<?php
    require_once(__DIR__ . '/../base/orch.php');
    class JobOrch extends BaseOrch {
        protected static $tableName = "job";
        protected static $fieldList = array("name", "bidDate", "subcontractorBidsDue", "prebidDateTime","prebidAddress","bidEmail","bonding","taxible");
    }