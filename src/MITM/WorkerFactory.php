<?php

declare(strict_types=1);

namespace PHPrivoxy\Proxy\MITM;

use Workerman\Worker;
use \Throwable;
use PHPrivoxy\Proxy\ProxyException;

class WorkerFactory
{
    private string $defaultMitmHost = '127.0.0.1';
    private string $mitmHost; // MITM worker host.
    private int $mitmPort; // MITM worker port.
    private ContextProvider $contextProvider;

    public function __construct(?ContextProvider $contextProvider = null, ?string $mitmHost = '127.0.0.1')
    {
        if (null === $contextProvider) {
            $contextProvider = new ContextProvider();
        }
        $this->contextProvider = $contextProvider;

        if (empty($mitmHost)) {
            $mitmHost = $this->defaultMitmHost;
        }
        $this->mitmHost = $mitmHost;
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
}
