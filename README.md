# Znojil HTTP

[![License](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)
[![PHP](https://img.shields.io/badge/php-%5E8.1-777bb4.svg)](https://www.php.net/)

**Lightweight, strict, and robust implementation of PSR-7, PSR-17, and PSR-18 standards.**

This library provides a clean HTTP Client (wrapper around cURL) and a complete set of HTTP Message objects (Request, Response, Stream, URI, etc.) strictly following PHP standards. It is designed to be lightweight with zero unnecessary dependencies.

## 📦 Features
- **PSR-7** Implementation (HTTP Message interfaces)
- **PSR-17** Implementation (HTTP Factories)
- **PSR-18** Implementation (HTTP Client)
- **Zero dependencies** (production)
- Strict types & PHPStan Level Max compatible
- Immutable objects design

## 🚀 Installation
Install via Composer:

```bash
composer require znojil/http
```

## 📖 Usage

### 1. Sending a Request (Client)
The Client automatically detects the provided ResponseFactory (PSR-17). If none is provided, it uses the internal implementation.

```php
use Znojil\Http\Client;
use Znojil\Http\RequestFactory;

$client = new Client;
$factory = new RequestFactory;

// Create a PSR-7 Request
$request = $factory->postJson('https://api.example.com/users', [
	'name' => 'John Doe',
	'role' => 'admin'
]);

// Send Request (PSR-18)
$response = $client->sendRequest($request);

echo $response->getStatusCode(); // 201
echo (string) $response->getBody(); // {"id": 1, ...}
```

### 2. Handling Incoming Request (Server)
Ideal for API endpoints or webhook processing.

```php
use Znojil\Http\Message\ServerRequest;

// Create request from PHP globals ($_GET, $_POST, $_FILES...)
$request = ServerRequest::fromGlobals();

$method = $request->getMethod();
$queryParams = $request->getQueryParams();
$body = $request->getParsedBody();

// Working with Uploaded Files
$files = $request->getUploadedFiles();
if (isset($files['document']) && $files['document']->isOk()) {
	$files['document']->moveTo('/storage/uploads/doc.pdf');
}
```

### 3. Using Factories (PSR-17)
You can use the factories to create any PSR-7 object manually.

```php
use Znojil\Http\Psr17Factory;

$factory = new Psr17Factory;

$uri = $factory->createUri('https://example.com');
$stream = $factory->createStream('Hello World');
$response = $factory->createResponse(200)->withBody($stream);
```

## 📄 License
This library is open-source software licensed under the [MIT license](https://choosealicense.com/licenses/mit/).
