<?php

declare(strict_types=1);

namespace PHPrivoxy\Proxy;

use PHPrivoxy\Core\TcpConnectionHandlerInterface;
use PHPrivoxy\Core\ConnectionParameters;
use Workerman\Connection\TcpConnection;

abstract class AbstractTcpHandler implements TcpConnectionHandlerInterface
{
    protected TcpConnection $localConnection;
    protected ?ConnectionParameters $connectionParameters;
    protected ?string $host;
    protected ?int $port;
    protected ?string $method;
    protected ?string $httpVersion;
    protected ?string $startBuffer;

    /*
     * Implementation of handle method from TcpConnectionHandlerInterface.
     */
    public function handle(TcpConnection $connection, ?ConnectionParameters $connectionParameters = null): void
    {
        $this->init($connectionParameters);
        $this->localConnection = $connection;
    }

    private function init(?ConnectionParameters $connectionParameters): void
    {
        $this->connectionParameters = $connectionParameters;
        if (null === $connectionParameters) {
            return;
        }

        $this->host = $connectionParameters->getHost();
        $this->port = $connectionParameters->getPort();
        $this->method = $connectionParameters->getMethod();
        $this->httpVersion = $connectionParameters->getHttpVersion();
        $this->startBuffer = $connectionParameters->getStartBuffer();
    }
}
