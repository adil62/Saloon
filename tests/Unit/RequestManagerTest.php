<?php

use Psr\Http\Message\RequestInterface;
use Sammyjo20\Saloon\Managers\RequestManager;
use Sammyjo20\Saloon\Tests\Resources\Requests\HeaderRequest;
use Sammyjo20\Saloon\Tests\Resources\Requests\UserRequestWithBoot;
use Sammyjo20\Saloon\Tests\Resources\Requests\ReplaceConfigRequest;
use Sammyjo20\Saloon\Tests\Resources\Requests\ReplaceHeaderRequest;
use Sammyjo20\Saloon\Tests\Resources\Requests\TrailingSlashRequest;
use Sammyjo20\Saloon\Tests\Resources\Requests\NoTrailingSlashRequest;
use Sammyjo20\Saloon\Tests\Resources\Requests\UserRequestWithBootConnector;

test('a request is built up correctly', function () {
    $requestManager = new RequestManager(new HeaderRequest());

    // Manually prepare the message

    $requestManager->prepareForFlight();

    expect($requestManager->getHeaders())->toEqual([
        'Accept' => 'application/json',
        'Content-Type' => 'application/json',
        'X-Connector-Header' => 'Sam', // Added by connector
        'X-Custom-Header' => 'Howdy', // Added by request
    ]);

    expect($requestManager->getConfig())->toEqual([
        'json' => [
            'foo' => 'bar', // Added by feature
        ],
        'http_errors' => false, // Added manually in connector
        'timeout' => 5, // Added manually in request
        'debug' => true, // Added by connector feature
    ]);
});

test('a request headers replace connectors headers', function () {
    $requestManager = new RequestManager(new ReplaceHeaderRequest());

    $requestManager->prepareForFlight();

    expect($requestManager->getHeaders())->toHaveKey('X-Connector-Header', 'Howdy');
});

test('a request config replace connectors config', function () {
    $requestManager = new RequestManager(new ReplaceConfigRequest());

    $requestManager->prepareForFlight();

    expect($requestManager->getConfig())->toHaveKey('debug', false);
});

test('the boot method can add functionality in connectors', function () {
    $requestManager = new RequestManager(new UserRequestWithBootConnector());
    $requestManager->prepareForFlight();

    expect($requestManager->getHeaders())->toHaveKey('X-Connector-Boot-Header', 'Howdy!');
});

test('the boot method can add functionality in requests', function () {
    $requestManager = new RequestManager(new UserRequestWithBoot());
    $requestManager->prepareForFlight();

    expect($requestManager->getHeaders())->toHaveKey('X-Request-Boot-Header', 'Yee-haw!');
});

test('the requirement for a trailing slash is enabled by default', function () {
    $request = new TrailingSlashRequest();

    $request->addHandler('captureBaseUrl', function (callable $handler) {
        return function (RequestInterface $request, array $options) use ($handler) {
            expect($request->getUri()->getPath())->toEqual('/api/');

            return $handler($request, $options);
        };
    });

    $request->send();
});

test('the trailing slash is removed when disabled from the request if the endpoint is an empty string', function () {
    $request = new NoTrailingSlashRequest();

    $request->addTrailingSlashAfterBaseUrl = false;

    $request->addHandler('captureBaseUrl', function (callable $handler) {
        return function (RequestInterface $request, array $options) use ($handler) {
            expect($request->getUri()->getPath())->toEqual('/api');

            return $handler($request, $options);
        };
    });

    $request->send();
});
