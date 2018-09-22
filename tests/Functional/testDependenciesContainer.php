<?php

use Slim\App;

class TestContainer {
    private static $container;

    public static function getContainer() {
        if(!isset(self::$container)) {
            $app = new App();
            // Set up dependencies
		    require __DIR__ . '/testDependencies.php';
            self::$container = $app->getContainer();
        }
        return self::$container;
    }
}