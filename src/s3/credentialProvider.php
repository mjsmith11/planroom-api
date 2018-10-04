<?php
    namespace s3;

    use GuzzleHttp\Promise;
    use GuzzleHttp\Promise\RejectedPromise;

    class CredentialProvider {
        public static function Provider($container)
        {
            $container['logger']->debug("Reading AWS Credentials");
            try {
                $jsonString = file_get_contents(__DIR__ . '/../../config.json');
                $config = json_decode($jsonString, true);
                $key = $config['aws']['key'];
                $secret = $config['aws']['secret'];
                unset($config);
                if ($key && $secret) {
                    return Promise\promise_for(
                        new Credentials($key, $secret)
                    );
                } else {
                    unset($key, $secret);
                    $container['logger']->alert('AWS Credentials Not Found');
                    return new RejectedPromise(new CredentialsException('Could not find credentials in config.json'));
                }
            } catch (\Throwable $e) {
                unset($key, $secret);
                $container['logger']->alert('Exception reading AWS Credentials', array('message' => $e.getMessage()));
                return new RejectedPromise(new CredentialsException('Error parsing config file'));
            }
        }
    }
