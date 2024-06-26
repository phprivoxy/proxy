<?php

declare(strict_types=1);

namespace PHPrivoxy\Proxy\MITM;

use Workerman\Worker;
use \Throwable;
use PHPrivoxy\Proxy\ProxyException;

class WorkerFactory
{
    private string $mitmHost; // MITM worker host.
    private int $mitmPort; // MITM worker port.

    public function __construct(string $mitmHost = '127.0.0.1')
    {
        $this->setMitmHost($mitmHost);
        $this->contextProvider = new ContextProvider();
    }

    public function getWorker(string $host, int $port)
    {
        do {
            $mitmPort = rand(1024, 65535);
            $address = 'http://' . $this->mitmHost . ':' . $mitmPort;

            try {
                if (443 === $port) { // TODO: define SSL not only by port value (=443).
                    $context = $this->contextProvider->getContext($host);
                    $mitmWorker = new Worker($address, $context);
                    $mitmWorker->transport = 'ssl';
                } else {
                    $mitmWorker = new Worker($address);
                }
            } catch (Throwable $exception) {
                $mitmWorker = false;
            }
        } while (false === $mitmWorker);

        $mitmWorker->count = 1;
        $mitmWorker->reusePort = true;
        $mitmWorker->name = 'MITM';

        return $mitmWorker;
    }

    private function setMitmHost(string $mitmHost): void
    {
        $mitmHost = trim($mitmHost);
        if (empty($mitmHost)) {
            throw new ProxyException('Incorrect MITM worker host.');
        }
        $this->mitmHost = $mitmHost;
    }
}
