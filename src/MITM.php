<?php

declare(strict_types=1);

namespace PHPrivoxy\Proxy;

use Psr\Http\Server\RequestHandlerInterface;
use Workerman\Connection\TcpConnection;
use Workerman\Connection\AsyncTcpConnection;
use Workerman\Protocols\Http;
use Workerman\Psr7\ServerRequest;
use Workerman\Worker;
use PHPrivoxy\Core\ProxyException;
use PHPrivoxy\Proxy\MITM\WorkerFactory;
use PHPrivoxy\Proxy\MITM\WorkermanResponseDecorator;

class MITM extends AbstractProxy
{
    protected RequestHandlerInterface $handler;
    protected Worker $mitmWorker;
    protected WorkerFactory $mitmWorkerFactory;

    public function __construct(RequestHandlerInterface $handler)
    {
        $this->handler = $handler;
        $this->mitmWorkerFactory = new WorkerFactory(); // TODO: that do not initialize again every time (a separate Worker?)
    }

    protected function prepare(): void
    {
        $this->mitmWorker = $this->mitmWorkerFactory->getWorker($this->host, $this->port);
        $this->mitmWorker->run();

        Http::requestClass(ServerRequest::class);
        $this->mitmWorker->onMessage = function (TcpConnection $connection, ServerRequest $request) {
            $request = $this->sanitizeWorkermanServerRequest($request);
            $response = $this->handler->handle($request);
            $response = $response->withoutHeader('Transfer-Encoding');
            $response = new WorkermanResponseDecorator($response);
            $connection->send($response);
            $this->mitmWorker->stop();
        };
    }

    protected function createRemoteConnection(): AsyncTcpConnection
    {
        $mitmHost = $this->getWorkerHost($this->mitmWorker);
        $mitmPort = $this->getWorkerPort($this->mitmWorker);

        if (empty($mitmHost)) {
            throw new ProxyException('Empty MITM host.');
        }
        if (empty($mitmPort)) {
            throw new ProxyException('Empty MITM port.');
        }

        return new AsyncTcpConnection('tcp://' . $mitmHost . ':' . $mitmPort);
    }

    protected function sanitizeWorkermanServerRequest(ServerRequest $request): ServerRequest
    {
        $uri = $request->getUri();
        $port = $uri->getPort();
        $host = $uri->getHost();
        $scheme = $uri->getScheme();

        $updateRequest = false;
        if (empty($port)) {
            $updateRequest = true;
            $port = $this->port;
            $uri = $uri->withPort($port);
        }
        if (empty($host)) {
            $updateRequest = true;
            $uri = $uri->withHost($this->host);
        }
        if (empty($scheme)) {
            $updateRequest = true;
            $scheme = (443 === $port) ? 'https' : 'http';
            $uri = $uri->withScheme($scheme);
        }

        if ($updateRequest) {
            $request = $request->withUri($uri);
        }

        return $request;
    }
}
