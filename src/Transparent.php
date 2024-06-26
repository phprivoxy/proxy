<?php

declare(strict_types=1);

namespace PHPrivoxy\Proxy;

use Workerman\Connection\AsyncTcpConnection;

class Transparent extends AbstractProxy
{
    protected function createRemoteConnection(): AsyncTcpConnection
    {
        if (empty($this->host)) {
            throw new ProxyException('Empty host.');
        }
        if (empty($this->port)) {
            throw new ProxyException('Empty port.');
        }

        return new AsyncTcpConnection('tcp://' . $this->host . ':' . $this->port);
    }
}
