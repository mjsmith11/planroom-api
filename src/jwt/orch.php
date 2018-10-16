<?php
namespace Planroom\JWT;

require_once(__DIR__ . "/../config/configReader.php");

use \Firebase\JWT\JWT;
use ConfigReader;

class Orch {
    public static function getContractorToken($email, $container) {
        //$container['info']->debug('Generating Token For Contractor User', array('email' => $email));
        $config = ConfigReader::getJwtInfo();
        $secret = $config['secret'];
        $validSeconds = $config['contractorExp'] * 60;
        $exp = time() + $validSeconds;
        $token = array(
            "exp" => $exp,
            "email" => $email
        );
        return JWT::encode($token, $secret, 'HS512');

    }
}