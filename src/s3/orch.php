<?php
	namespace Planroom\S3;

	require_once __DIR__ . '/../config/configReader.php';
	require_once __DIR__ . '/../db/orchestrators/jobOrch.php';

	use ConfigReader;
	use JobOrch;
	use Aws\S3\PostObjectV4;

	/**
	 * This class contains methods for interacting with AWS S3
	 * @SuppressWarnings lineLength
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
	/**
	 * @OA\Schema(
	 * 	schema="plan",
	 * 	description="plan stored in s3",
	 * 	type="object",
	 * 	@OA\Property(
	 * 		property="key",
	 * 		type="string",
	 * 		example="25/CMS_Addendum_4.pdf"
	 * 	),
	 * 	@OA\Property(
	 * 		property="url",
	 * 		type="string",
	 * 		example="https://benchmark-planroom.s3.amazonaws.com/25/CMS_Addendum_4.pdf?X-Amz-Content-Sha256=UNSIGNED-PAYLOAD&X-Amz-Algorithm=AWS4-HMAC-SHA256&X-Amz-Credential=AKIAING2TBX3PCHBVN3Q%2F20181116%2Fus-east-1%2Fs3%2Faws4_request&X-Amz-Date=20181116T002802Z&X-Amz-SignedHeaders=host&X-Amz-Expires=900&X-Amz-Signature=ec7b7fcf087de79afc1f1849ddd2cf5f8bc00238089630ffc8819758f4fcc534"
	 * 	)
	 * )
	 * 
	 * @OA\Schema(
	 * 	schema="presigned_post",
	 * 	description="presigned post request for AWS S3",
	 * 	type="object",
	 * 	@OA\Property(
	 * 		property="postEndpoint",
	 * 		type="string",
	 * 		example="https://planroom-dev-1.s3.amazonaws.com"
	 * 	),
	 * 	@OA\Property(
	 * 		property="signature",
	 * 		type="object",
	 * 		@OA\Property(
	 * 			property="key",
	 * 			type="string",
	 * 			example="21/abcdef.pdf"
	 * 		),
	 * 		@OA\Property(
	 * 			property="X-Amz-Credential",
	 * 			type="string",
	 * 			example="AKIAJY4YIUEHRS5T7NIA/20181116/us-east-1/s3/aws4_request"
	 * 		),
	 * 		@OA\Property(
	 * 			property="X-Amz-Algorithm",
	 * 			type="string",
	 * 			example="AWS4-HMAC-SHA256"
	 * 		),
	 * 		@OA\Property(
	 * 			property="X-Amz-Date",
	 * 			type="string",
	 * 			example="20181116T005417Z"
	 * 		),
	 * 		@OA\Property(
	 * 			property="Policy",
	 * 			type="string",
	 * 			example="eyJleHBpcmF0aW9uIjoiMjAxOC0xMS0xNlQwMTowNDoxN1oiLCJjb25kaXRpb25zIjpbeyJidWNrZXQiOiJwbGFucm9vbS1kZXYtMSJ9LHsia2V5IjoiMjFcL2FiY2RlZi5wZGYifSx7IlgtQW16LURhdGUiOiIyMDE4MTExNlQwMDU0MTdaIn0seyJYLUFtei1DcmVkZW50aWFsIjoiQUtJQUpZNFlJVUVIUlM1VDdOSUFcLzIwMTgxMTE2XC91cy1lYXN0LTFcL3MzXC9hd3M0X3JlcXVlc3QifSx7IlgtQW16LUFsZ29yaXRobSI6IkFXUzQtSE1BQy1TSEEyNTYifV19"
	 * 		),
	 * 		@OA\Property(
	 * 			property="X-Amz-Signature",
	 * 			type="string",
	 * 			example="7915c7deb548d68773ff517ffdf9bbf86d53a0ff9a92f94ef8ef3f838ca4f7b3"
	 * 		)
	 * 	)
	 * )
	 */