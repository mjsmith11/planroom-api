<?php

namespace Tests\Functional;

use Slim\App;
use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Http\Environment;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPUnit\Framework\TestCase;

/**
 * This is an example class that shows how you could set up a method that
 * runs the application. Note that it doesn't cover all use-cases and is
 * tuned to the specifics of this skeleton app, so if your needs are
 * different, you'll need to change it.
 * 
 * @SuppressWarnings functionMaxParameters
 */
class BaseTestCase extends TestCase {
	/**
	 * Process the application given a request method and URI
	 *
	 * @param string $requestMethod the request method (e.g. GET, POST, etc.)
	 * @param string $requestUri the request URI
	 * @param array|object|null $requestData the request data
	 * @param boolean $s3Mock Should S3 be mocked for getting Objects
	 * @param boolean $middleware Should middleware be used
	 * @param string $token provide a token to be used
	 * @return \Slim\Http\Response
	 */
	public function runApp($requestMethod, $requestUri, $requestData = null, $s3Mock = false, $middleware = true, $token = null) {
		// Create a mock environment for testing with
		$environment = Environment::mock(
			[
				'REQUEST_METHOD' => $requestMethod,
				'REQUEST_URI' => $requestUri
			]
		);

		// Set up a request object based on the environment
		$request = Request::createFromEnvironment($environment);

		if (isset($token)) {
			$request = $request->withHeader('Planroom-Authorization', 'Bearer ' . $token);
		}

		// Add request data, if it exists
		if (isset($requestData)) {
			$request = $request->withParsedBody($requestData);
		}

		// Set up a response object
		$response = new Response();

		// Use the application settings
		$settings = require __DIR__ . '/../../src/settings.php';

		// Instantiate the application
		$app = new App($settings);

		// Set up dependencies
		require __DIR__ . '/testDependencies.php';
		if ($s3Mock) {
			$stub = $this->createMock(\Aws\S3\S3Client::class);
			$stub->method('getIterator')
				->willReturn([[ 'Key' => 'firstObj'], [ 'Key' => 'secondObj']]);
			$stub->method('getCommand')
				->willReturn(new \Aws\Command("dummy-command"));
			$stub->method('createPresignedRequest')
				->willReturn(new \GuzzleHttp\Psr7\Request('GET', 'www.test.com'));

			$container = $app->getContainer();
			unset($container['S3Client']);
			$container['S3Client'] = $stub;
		}

		$stub = $this->createMock(PHPMailer::class);
		$stub->method('clearAddresses')
			->willReturn('');
		$stub->method('addAddress')
			->willReturn('');
		$stub->method('isHTML')
			->willReturn('');
		$stub->method('send')
			->willReturn('');
		$container = $app->getContainer();
		unset($container['mailer']);
		$container['mailer'] = $stub;

		// Register middleware
		if ($middleware) {
			require __DIR__ . '/../../src/middleware.php';
		}

		// Register routes
		require __DIR__ . '/../../src/routes.php';

		$_SERVER['HTTP_ORIGIN'] = 'localhost';

		// Process the application
		$response = $app->process($request, $response);

		// Return the response
		return $response;
	}
}
