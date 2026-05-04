<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use ConvertAndStore\Client;
use ConvertAndStore\Exception\ApiException;
use ConvertAndStore\Exception\AuthenticationException;
use ConvertAndStore\Exception\NetworkException;

$client = new Client('cas_your_api_key_here');

try {
    $status = $client->getStatus();
    $me = $client->getMe();
    $tools = $client->listTools();

    print_r($status);
    print_r($me);
    print_r(array_slice($tools, 0, 3));

    $conversion = $client->convert(
        'jpg-to-png',
        __DIR__ . '/example.jpg',
        [
            'quality' => 90,
        ]
    );

    print_r($conversion);
} catch (AuthenticationException $exception) {
    echo 'Auth error: ' . $exception->getMessage() . PHP_EOL;
} catch (NetworkException $exception) {
    echo 'Network error: ' . $exception->getMessage() . PHP_EOL;
} catch (ApiException $exception) {
    echo 'API error: ' . $exception->getMessage() . PHP_EOL;
}
