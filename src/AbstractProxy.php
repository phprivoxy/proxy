<?php

declare(strict_types=1);

namespace PHPrivoxy\Proxy;

use PHPrivoxy\Core\ConnectionParameters;
use Workerman\Connection\TcpConnection;
use Workerman\Connection\AsyncTcpConnection;
use Workerman\Worker;

abstract class AbstractProxy extends AbstractTcpHandler
{
    protected TcpConnection $localConnection;
    protected AsyncTcpConnection $remoteConnection;
    protected ?ConnectionParameters $connectionParameters;

    abstract protected function createRemoteConnection(): AsyncTcpConnection;

    /*
     * This method may be modified for your needs in your class realization.
     */
    protected function prepare(): void
    {

    }

    public function handle(TcpConnection $connection, ?ConnectionParameters $connectionParameters = null): void
    {
        parent::handle($connection, $connectionParameters);

        $this->prepare();

        $this->remoteConnection = $this->createRemoteConnection();

        if ($this->method !== 'CONNECT') {
            $this->remoteConnection->send($this->startBuffer);
        } else {
            $this->localConnection->send("HTTP/1.1 200 Connection Established\r\n\r\n");
        }

        $this->remoteConnection->pipe($this->localConnection);
        $this->localConnection->pipe($this->remoteConnection);
        $this->remoteConnection->connect();
    }

    protected function getWorkerHost(Worker $worker): string
    {
        return parse_url($worker->getSocketName(), PHP_URL_HOST);
    }

    protected function getWorkerPort(Worker $worker): int
    {
        return parse_url($worker->getSocketName(), PHP_URL_PORT);
    }
}
