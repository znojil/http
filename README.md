# Znojil HTTP

[![Latest Stable Version](https://img.shields.io/packagist/v/znojil/http)](https://packagist.org/packages/znojil/http)
[![PHP Version Require](https://img.shields.io/packagist/dependency-v/znojil/http/php)](https://packagist.org/packages/znojil/http)
[![License](https://img.shields.io/packagist/l/znojil/http)](LICENSE)
[![Tests](https://github.com/znojil/http/actions/workflows/tests.yml/badge.svg?branch=main)](https://github.com/znojil/http/actions/workflows/tests.yml)

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

#### Client Configuration

The Client constructor accepts optional parameters for configuring base URI, default headers, and cURL options:

```php
use Znojil\Http\Client;

$client = new Client(
	'https://api.example.com/v1',
	[
		'Authorization' => 'Bearer token123',
		'Accept' => 'application/json'
	],
	[
		CURLOPT_TIMEOUT => 30,
		CURLOPT_CONNECTTIMEOUT => 5
	]
);

// Now you can use relative URLs - base URI will be automatically combined
$response = $client->sendRequest($factory->get('/users?limit=10'));
// Requests: https://api.example.com/v1/users?limit=10
```

> The client does not follow redirects by default (3xx responses are returned to the caller). Enable via `CURLOPT_FOLLOWLOCATION => true` in constructor or per-request options.

#### RequestFactory Helper Methods

The `RequestFactory` provides convenient methods for common HTTP operations:

```php
use Znojil\Http\RequestFactory;

$factory = new RequestFactory;

// GET request with query parameters
$request = $factory->get('https://api.example.com/users', ['page' => 1, 'limit' => 10]);

// POST with JSON body
$request = $factory->postJson('https://api.example.com/users', ['name' => 'John']);

// POST with form data
$request = $factory->post('https://api.example.com/login', [
	'username' => 'john',
	'password' => 'secret'
], ['Content-Type' => 'application/x-www-form-urlencoded']);

// PUT, PATCH, DELETE
$request = $factory->putJson('https://api.example.com/users/1', ['name' => 'Jane']);
$request = $factory->patchJson('https://api.example.com/users/1', ['status' => 'active']);
$request = $factory->delete('https://api.example.com/users/1');
```

#### File Upload (Multipart)

For file uploads with additional form fields, use cURL's native `CURLFile` via `CURLOPT_POSTFIELDS`:

```php
use Znojil\Http\Client;
use Znojil\Http\RequestFactory;

$client = new Client;
$factory = new RequestFactory;

$request = $factory->post('https://api.example.com/upload');

$response = $client->sendRequest($request, [
	CURLOPT_POSTFIELDS => [
		'title' => 'My Document',
		'category' => 'reports',
		'file' => new \CURLFile('/path/to/document.pdf', 'application/pdf', 'document.pdf')
	]
]);
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
if(isset($files['document']) && $files['document']->isOk()){
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

## ⚠️ Error Handling

The library throws PSR-18 compliant exceptions:

```php
use Znojil\Http\Client;
use Znojil\Http\Exception\NetworkException;
use Znojil\Http\Exception\ClientException;

$client = new Client;

try{
	$response = $client->sendRequest($request);
}catch(NetworkException $e){
	// Network-level errors (DNS failure, connection timeout, etc.)
	echo 'Network error: ' . $e->getMessage();
	$failedRequest = $e->getRequest(); // Access the original request
}catch(ClientException $e){
	// Client initialization errors
	echo 'Client error: ' . $e->getMessage();
}
```

## 📄 License

This library is open-source software licensed under the [MIT license](LICENSE).
