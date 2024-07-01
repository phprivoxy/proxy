<?php

declare(strict_types=1);

namespace PHPrivoxy\Proxy;

use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Client\NetworkExceptionInterface;
use Workerman\Connection\TcpConnection;
use Workerman\Connection\AsyncTcpConnection;
use Workerman\Protocols\Http;
use Workerman\Psr7\ServerRequest;
use PHPrivoxy\Proxy\ProxyException;
use PHPrivoxy\Proxy\MITM\WorkerFactory;
use PHPrivoxy\Proxy\MITM\ContextProvider;
use PHPrivoxy\Proxy\MITM\WorkermanResponseDecorator;
use \Exception;

class MITM extends PSR15Proxy
{
    private ?ContextProvider $contextProvider;
    private ?string $mitmLogDirectory;
    private ?string $mitmHost; // MITM worker host.
    private WorkerFactory $mitmWorkerFactory;

    public function __construct(
            RequestHandlerInterface $handler,
            ?ContextProvider $contextProvider = null,
            ?string $mitmLogDirectory = null,
            ?string $mitmHost = null
    )
    {
        parent::__construct($handler);
        $this->contextProvider = $contextProvider;
        $this->mitmLogDirectory = $mitmLogDirectory;
        $this->mitmHost = $mitmHost;

        // TODO: that do not initialize again every time (a separate Worker?)
        $this->mitmWorkerFactory = new WorkerFactory($this->contextProvider, $this->mitmLogDirectory, null, $this->mitmHost);
    }

    protected function prepare(): void
    {
        $this->mitmWorker = null;
        while (null === $this->mitmWorker) {
            // Even for a obviously non-existent site (a la https://this.site.not-exist/) it generates an SSL certificate.
            // It is a fundamental disadvantage.
            $this->mitmWorker = $this->mitmWorkerFactory->getWorker($this->host, $this->port);

            Http::requestClass(ServerRequest::class);
            $this->mitmWorker->onMessage = function (TcpConnection $connection, ServerRequest $request) {
                $request = $this->sanitizeWorkermanServerRequest($request);

                // By default, Workerman worker (which run from PHPrivoxy\Core\Server) will try it 10 attempts.
                try {
                    $response = $this->handler->handle($request);
                } catch (NetworkExceptionInterface $e) {
                    $connection->close();
                    $this->mitmWorker->stop();
                    return;
                }

                $response = $response->withoutHeader('Transfer-Encoding');
                $response = new WorkermanResponseDecorator($response);
                $connection->send($response);
                $this->mitmWorker->stop();
            };

            try {
                // May be PHP Warning: stream_socket_server(): Unable to connect to tcp://127.0.0.1:xxxxx (Address already in use)
                @$this->mitmWorker->run();
            } catch (Exception $exception) {
                $this->mitmWorker->stop();
                $this->mitmWorker = null;
            }
        }
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
