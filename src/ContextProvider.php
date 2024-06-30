<?php

declare(strict_types=1);

namespace PHPrivoxy\Proxy\MITM;

use PHPrivoxy\Core\RootPath;
use PHPrivoxy\X509\RootCertificateCreator;
use PHPrivoxy\X509\ServerCertificateCreator;
use PHPrivoxy\X509\DTO\ImmutableNames;
use PHPrivoxy\X509\DTO\CertificateProperties;
use PHPrivoxy\X509\DTO\PrivateKeyProperties;

class ContextProvider
{
    use RootPath;

    private string $rootPrivateKeyFileName = 'PHPrivoxy.key';
    private string $rootCertificateFileName = 'PHPrivoxy_CA.crt';
    private string $defaultRootCertificateDirName = '/CA/';
    private string $defaultCertificatesDirName = '/certificates/';
    private int $numberOfDays = 365241; // 1000 years
    private ServerCertificateCreator $certificateCreator;

    public function __construct(?string $rootCertificateDir = null, ?string $certificatesDir = null)
    {
        $appRootPath = self::getRootPath();
        $rootCertificateDir = (null !== $rootCertificateDir) ? $rootCertificateDir : $appRootPath . $this->defaultRootCertificateDirName;
        $this->certificatesDir = (null !== $certificatesDir) ? $certificatesDir : $appRootPath . $this->defaultCertificatesDirName;

        if (!is_dir($rootCertificateDir)) {
            @mkdir($rootCertificateDir, 0755, true);
        }
        if (!is_dir($this->certificatesDir)) {
            @mkdir($this->certificatesDir, 0755, true);
        }

        $this->rootPrivateKeyFile = $rootCertificateDir . $this->rootPrivateKeyFileName;
        $this->rootCertificateFile = $rootCertificateDir . $this->rootCertificateFileName;

        $rootNames = new ImmutableNames('RU', 'PHP proxy', null, 'PHPrivoxy', null, 'PHPrivoxy Root CA');
        $rootCertificateProperties = new CertificateProperties($rootNames, $this->rootCertificateFile, $this->numberOfDays);
        $rootKey = new PrivateKeyProperties($this->rootPrivateKeyFile);
        $rootCertificateCreator = new RootCertificateCreator($rootCertificateProperties, $rootKey);
        $rootCertificateCreator->getCertificate();

        if (!($this->rootCertificateFile = realpath($this->rootCertificateFile))) {
            throw new ProxyException('Unable to get root certificate.');
        }

        $this->certificateCreator = new ServerCertificateCreator($rootCertificateProperties, $rootKey, $this->certificatesDir);
    }

    public function getContext(string $host): array
    {
        $host = trim($host);
        if (empty($host)) {
            throw new ProxyException('Empty host.');
        }

        $certificate = $this->certificateCreator->createCertificate($host);

        if (empty($certificateFile = $certificate->properties()->certificateFile())) {
            throw new ProxyException('Empty MITM certificate file name.');
        }
        if (!file_exists($certificateFile)) {
            throw new ProxyException("Certificate file don't exist.");
        }

        if (empty($privateKeyFile = $certificate->keyProperties()->privateKeyFile())) {
            throw new ProxyException('Empty MITM certificate private key file name.');
        }
        if (!file_exists($privateKeyFile)) {
            throw new ProxyException("Certificate private key file don't exist.");
        }

        $context = [
            'ssl' => [
                'cafile' => $this->rootCertificateFile,
                'local_cert' => $certificateFile,
                'local_pk' => $privateKeyFile,
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true,
            ]
        ];

        return $context;
    }
}
