<?php

declare(strict_types=1);

namespace PHPrivoxy\Proxy;

use Psr\Http\Server\RequestHandlerInterface;
use Workerman\Psr7\ServerRequest;
use Workerman\Worker;

abstract class PSR15Proxy extends AbstractProxy
{
    protected RequestHandlerInterface $handler;
    protected Worker $mitmWorker;

    public function __construct(RequestHandlerInterface $handler)
    {
        $this->handler = $handler;
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
