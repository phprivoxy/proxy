<?php

declare(strict_types=1);

namespace PHPrivoxy\Proxy;

use Psr\Http\Server\RequestHandlerInterface;
use Workerman\Connection\TcpConnection;
use Workerman\Connection\AsyncTcpConnection;
use Workerman\Protocols\Http;
use Workerman\Psr7\ServerRequest;
use PHPrivoxy\Core\ProxyException;
use PHPrivoxy\Proxy\MITM\WorkerFactory;
use PHPrivoxy\Proxy\MITM\ContextProvider;
use PHPrivoxy\Proxy\MITM\WorkermanResponseDecorator;

class MITM extends PSR15Proxy
{
    private ?ContextProvider $contextProvider;
    private ?string $mitmHost; // MITM worker host.

    public function __construct(
            RequestHandlerInterface $handler,
            ?ContextProvider $contextProvider = null,
            ?string $mitmHost = null
    )
    {
        parent::__construct($handler);
        $this->contextProvider = $contextProvider;
        $this->mitmHost = $mitmHost;
    }

    protected function prepare(): void
    {
        $this->mitmWorkerFactory = new WorkerFactory($this->contextProvider, $this->mitmHost); // TODO: that do not initialize again every time (a separate Worker?)
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
}
