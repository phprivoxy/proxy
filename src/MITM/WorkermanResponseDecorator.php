<?php

declare(strict_types=1);

namespace PHPrivoxy\Proxy\MITM;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\StreamInterface;

class WorkermanResponseDecorator implements ResponseInterface
{
    private $response;

    public function __construct(ResponseInterface $response)
    {
        $this->response = $response;
    }

    public function __toString(): string
    {
        return $this->responseToString($this);
    }

    // From Workerman\PSR7\src\functions
    private function responseToString(ResponseInterface $message)
    {
        $stream = $message->getBody();
        $stream->rewind();
        $content = (string) $stream;
        $stream->rewind();
        $size = $stream->getSize();

        $msg = 'HTTP/' . $message->getProtocolVersion() . ' '
                . $message->getStatusCode() . ' '
                . $message->getReasonPhrase();
        $headers = $message->getHeaders();
        if (empty($headers)) {
            $msg .= "\r\nContent-Length: " . $size .
                    "\r\nContent-Type: text/html\r\nConnection: keep-alive\r\nServer: PHPrivoxy";
        } else {
            if ('' === $message->getHeaderLine('Transfer-Encoding') && '' === $message->getHeaderLine('Content-Length')) {
                $msg .= "\r\nContent-Length: " . $size;
            }
            if ('' === $message->getHeaderLine('Content-Type')) {
                $msg .= "\r\nContent-Type: text/html";
            }
            if ('' === $message->getHeaderLine('Connection')) {
                $msg .= "\r\nConnection: keep-alive";
            }
            if ('' === $message->getHeaderLine('Server')) {
                $msg .= "\r\nServer: PHPrivoxy";
            }
            foreach ($headers as $name => $values) {
                $msg .= "\r\n{$name}: " . implode(', ', $values);
            }
        }

        return "{$msg}\r\n\r\n" . $content;
    }

    public function getStatusCode(): int
    {
        return $this->response->getStatusCode();
    }

    public function withStatus(int $code, string $reasonPhrase = ''): ResponseInterface
    {
        return $this->response->withStatus($code, $reasonPhrase);
    }

    public function getReasonPhrase(): string
    {
        return $this->response->getReasonPhrase();
    }

    public function getProtocolVersion(): string
    {
        return $this->response->getProtocolVersion();
    }

    public function withProtocolVersion(string $version): MessageInterface
    {
        return $this->response->withProtocolVersion($version);
    }

    public function getHeaders(): array
    {
        return $this->response->getHeaders();
    }

    public function hasHeader(string $name): bool
    {
        return $this->response->hasHeader($name);
    }

    public function getHeader(string $name): array
    {
        return $this->response->getHeader($name);
    }

    public function getHeaderLine(string $name): string
    {
        return $this->response->getHeaderLine($name);
    }

    public function withHeader(string $name, $value): MessageInterface
    {
        return $this->response->withHeader($name, $value);
    }

    public function withAddedHeader(string $name, $value): MessageInterface
    {
        return $this->response->withAddedHeader($name, $value);
    }

    public function withoutHeader(string $name): MessageInterface
    {
        return $this->response->withoutHeader($name);
    }

    public function getBody(): StreamInterface
    {
        return $this->response->getBody();
    }

    public function withBody(StreamInterface $body): MessageInterface
    {
        return $this->response->withBody($body);
    }
}
