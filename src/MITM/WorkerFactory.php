<?php

declare(strict_types=1);

namespace PHPrivoxy\Proxy\MITM;

use PHPrivoxy\Core\ServerWorker;
use PHPrivoxy\Core\RootPath;
use \Exception;

class WorkerFactory
{
    use RootPath;

    private string $defaultLogDirectory = 'var/log';
    private string $defaultLogFileName = 'MITM.log';
    private string $defaultMitmHost = '127.0.0.1';
    private int $mitmPort; // MITM worker port.
    private ContextProvider $contextProvider;
    private string $mitmLogDirectory; // MITM worker log directory.
    private string $mitmLogFile; // MITM worker log file.
    private string $mitmHost; // MITM worker host.

    public function __construct(
            ?ContextProvider $contextProvider = null,
            ?string $mitmLogDirectory = null,
            ?string $mitmLogFile = null,
            ?string $mitmHost = null
    )
    {
        if (null === $contextProvider) {
            $contextProvider = new ContextProvider();
        }
        $this->contextProvider = $contextProvider;

        $this->mitmLogDirectory = empty($mitmLogDirectory) ? self::getRootPath() . '/' . $this->defaultLogDirectory : $mitmLogDirectory;
        $mitmLogFile = empty($mitmLogFile) ? $this->defaultLogFileName : $mitmLogFile;
        $this->mitmLogFile = $this->mitmLogDirectory . '/' . $mitmLogFile;
        $this->checkFile($this->mitmLogFile);

        if (empty($mitmHost)) {
            $mitmHost = $this->defaultMitmHost;
        }
        $this->mitmHost = $mitmHost;
    }

    public function getWorker(string $host, int $port): ServerWorker
    {
        if (443 === $port) { // TODO: define SSL not only by port value (=443).
            $context = $this->contextProvider->getContext($host);
        }

        do {
            $mitmPort = rand(1024, 65535);
            $address = 'http://' . $this->mitmHost . ':' . $mitmPort;

            try {
                if (443 === $port) { // TODO: define SSL not only by port value (=443).
                    $mitmWorker = new ServerWorker($address, $context);
                    $mitmWorker->transport = 'ssl';
                } else {
                    $mitmWorker = new ServerWorker($address);
                }
            } catch (Exception $exception) {
                $mitmWorker = false;
            }
        } while (false === $mitmWorker);

        $mitmWorker->count = 1;
        $mitmWorker->reusePort = false;
        $mitmWorker->name = 'MITM';
        $mitmWorker::$logFile = $this->mitmLogFile;

        return $mitmWorker;
    }
}
