<?php
    namespace Planroom\S3;

    require_once __DIR__ . '/../config/configReader.php';
    require_once __DIR__ . '/../db/orchestrators/jobOrch.php';

    use ConfigReader;
    use JobOrch;
    use Aws\S3\PostObjectV4;

    class S3Orch {
        public static function getPresignedPost($jobId, $filename, $container) {
            if (!JobOrch::exists($jobId, $container)) {
                $container['logger']->error('Trying to upload to job that doesn\'t exist', array('jobId' => $jobId));
                throw new \Exception('Job ' . $jobId . ' does not exist');
            }

            if (trim($filename) == '') {
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

            $expires = '+' . $config['urlExpiration'] . ' minutes';

            $postObject = new PostObjectV4(
                $container['S3Client'],
                $config['bucket'],
                [],
                $options,
                $expires
            );

            $retVal = array('formAttributes' => $postObject->getFormAttributes(), 'formInputs' => $postObject->getFormInputs());
            return $retVal;
        }
    }