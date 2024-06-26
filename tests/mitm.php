<?php

use PHPrivoxy\Core\Server;
use PHPrivoxy\Proxy\MITM;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Relay\Relay;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\BadResponseException;

require_once __DIR__ . '/../vendor/autoload.php';

class HttpClientMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $client = new Client();
        try {
            $response = $client->send($request, ['allow_redirects' => false]);
        } catch (ConnectException $e) {
            // Do something
        } catch (BadResponseException $e) {
            return $e->getResponse();
        }

        return $response;
    }
}

$httpClientMiddleware = new HttpClientMiddleware();
$queue = [$httpClientMiddleware];
$psr15handler = new Relay($queue);
$tcpHandler = new MITM($psr15handler);
$processes = 6; // Default 1.

new Server($tcpHandler, $processes);
