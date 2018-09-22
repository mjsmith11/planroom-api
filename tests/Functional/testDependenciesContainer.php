<?php

use Slim\App;

/**
 * Provides a singleton slim dependency container for testing purposes
 */
class TestContainer {
	private static $container;

	/**
	 * @returns Singleton slim container to be used in testing.
	 */
	public static function getContainer() {
		if (!isset(self::$container)) {
			$app = new App();
			// Set up dependencies
			require __DIR__ . '/testDependencies.php';
			self::$container = $app->getContainer();
		}
		return self::$container;
	}
}