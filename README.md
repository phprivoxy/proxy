# phprivoxy/proxy
## Core library for HTTP/HTTPS proxy building.

This PHP package based on Workerman framework (https://github.com/walkor/workerman) and will be useful for custom proxy servers creation.

### Requirements 
- **PHP >= 8.1**

### Installation
#### Using composer (recommended)
```bash
composer create phprivoxy/proxy
```

### Simple transparent proxy sample

```php
$handler = new PHPrivoxy\Proxy\Transparent();
new PHPrivoxy\Core\Server($handler);// By default, it listen all connections on 8080 port.
```
Configure your browser to work through a proxy server with the IP address 127.0.0.1 and port 8080.

Try to open any site on HTTP or HTTPS protocols. As sample, try to open https://php.net, https://google.com, https://microsoft.com.

This sample you also may find at "tests" directory.

Just run it:
```bash
php tests/transparent.php start
```


### Simple SSL MITM (Man In The Middle) proxy sample

```php
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

class HttpClientMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $client = new GuzzleHttp\Client();
        try {
            $response = $client->send($request, ['allow_redirects' => false]);
        } catch (GuzzleHttp\Exception\ConnectException $e) {
            // Do something
        } catch (GuzzleHttp\Exception\BadResponseException $e) {
            return $e->getResponse();
        }

        return $response;
    }
}

$httpClientMiddleware = new HttpClientMiddleware();
$queue = [$httpClientMiddleware];
$psr15handler = new Relay\Relay($queue);
$tcpHandler = new PHPrivoxy\Proxy\MITM($psr15handler);
$processes = 6; // Default 1.

new PHPrivoxy\Core\Server($tcpHandler, $processes);// By default, it listen all connections on 8080 port.
```
This sample you also may find at "tests" directory.

Just run it:
```bash
php tests/mitm.php start
```
On first run it create a self-signed SSL root certificate in CA subdirectory. Add this self-signed CA certificate in your browser trusted certificates!

For each site PHPrivoxy\Proxy\MITM will generate self-signed certificates. 

In this sample, we use simple PSR-15 compatible HttpClientMiddleware for site downloading. You also may add your own PSR-15 compatible Middlewares in queue for PSR-15 handler (modified Relay\Relay in this sample).

### License
MIT License See [LICENSE.MD](LICENSE.MD)
