<?php
	namespace Planroom\S3;

	require_once __DIR__ . '/../config/configReader.php';
	require_once __DIR__ . '/../db/orchestrators/jobOrch.php';

	use ConfigReader;
	use JobOrch;
	use Aws\S3\PostObjectV4;

	/**
	 * This class contains methods for interacting with AWS S3
	 */
	class S3Orch {
		/**
		 * Get a presigned post to upload a file to s3 for a job
		 * 
		 * @param jobId job to look add file to
		 * @param filename name of the file to upload
		 * @param container dependency container
		 * 
		 * @throws Exception on invalid params
		 * 
		 * @return object array with postEndpoint and signature info
		 */
		public static function getPresignedPost($jobId, $filename, $container) {
			if (!JobOrch::exists($jobId, $container)) {
				$container['logger']->error('Trying to upload to job that doesn\'t exist', array('jobId' => $jobId));
				throw new \Exception('Job ' . $jobId . ' does not exist');
			}

			if (trim($filename) === '') {
				$container['logger']->error('No Filename Specified');
				throw new \Exception('filename must be specified');
			}

			$container['logger']->info('Creating Presigned POST', array('jobId' => $jobId, 'filename' => $filename));
			$config = ConfigReader::getAwsConfig();
			$key = $jobId . "/" . $filename;

			$options = [
				['bucket' => $config['bucket']],
				['key' => $key]
			];

			$formInputs = ['key' => $key];

			$expires = '+' . $config['urlExpiration'] . ' minutes';

			$postObject = new PostObjectV4(
				$container['S3Client'],
				$config['bucket'],
				$formInputs,
				$options,
				$expires
			);

			$retVal = array('postEndpoint' => $postObject->getFormAttributes()['action'], 'signature' => $postObject->getFormInputs());
			return $retVal;
		}

		/**
		 * Returns all objects in s3 for a given job and creates presigned requests for them
		 * 
		 * @param jobId job to look for
		 * @param container dependency container
		 * 
		 * @throws Exception when the jobId doesn't exist in the database's job table
		 * 
		 * @return object array of arrays with key and url keys.
		 */
		public static function getObjectsByJob($jobId, $container) {
			if (!JobOrch::exists($jobId, $container)) {
				$container['logger']->error('Trying to get objects for a job that doesn\'t exist', array('jobId' => $jobId));
				throw new \Exception('Job ' . $jobId . ' does not exist');
			}
			$config = ConfigReader::getAwsConfig();
			$expires = '+' . $config['urlExpiration'] . ' minutes';

			$objects = $container['S3Client']->getIterator('ListObjects', array(
				"Bucket" => $config['bucket'],
				"Prefix" => $jobId . "/"
			));

			$result = [];

			foreach ($objects as $object) {
				$cmd = $container['S3Client']->getCommand('GetObject', [
					"Bucket" => $config['bucket'],
					"Key" => $object['Key']
				]);
				$request = $container['S3Client']->createPresignedRequest($cmd, $expires);
				$url = (string) $request->getUri();
				array_push($result, array('key' => $object['Key'], 'url' => $url));
			}
			return $result;
		}
	}