<?php
declare(strict_types=1);

namespace Znojil\Http\Tests\Unit;

use Tester\Assert;
use Znojil\Http\Message;
use Znojil\Http\Psr17Factory;

require __DIR__ . '/../bootstrap.php';

/**
 * @testCase
 */
final class Psr17FactoryTest extends \Tester\TestCase{

	private Psr17Factory $factory;

	protected function setUp(): void{
		parent::setUp();
		$this->factory = new Psr17Factory;
	}

	public function testRequest(): void{
		$request = $this->factory->createRequest('POST', 'http://example.com');

		Assert::type(Message\Request::class, $request);
		Assert::same('POST', $request->getMethod());
		Assert::same('http://example.com', (string) $request->getUri());
	}

	public function testResponse(): void{
		$response = $this->factory->createResponse(404, 'Not found');

		Assert::type(Message\Response::class, $response);
		Assert::same(404, $response->getStatusCode());
		Assert::same('Not found', $response->getReasonPhrase()); // custom

		$default = $this->factory->createResponse();
		Assert::same(200, $default->getStatusCode());
		Assert::same('OK', $default->getReasonPhrase()); // default
	}

	public function testServerRequest(): void{
		$serverRequest = $this->factory->createServerRequest('POST', 'http://example.com', ['SERVER_NAME' => 'localhost']);

		Assert::type(Message\ServerRequest::class, $serverRequest);
		Assert::same('POST', $serverRequest->getMethod());
		Assert::same('http://example.com', (string) $serverRequest->getUri());
		Assert::same(['SERVER_NAME' => 'localhost'], $serverRequest->getServerParams());
	}

	public function testStream(): void{
		$stream = $this->factory->createStream('test content');

		Assert::type(Message\Stream::class, $stream);
		Assert::same('test content', (string) $stream);

		$default = $this->factory->createStream();
		Assert::same('', (string) $default);
	}

	public function testStreamFromFile(): void{
		$tmpFilename = TempDir . '/file.txt';
		file_put_contents($tmpFilename, 'file content');

		$stream = $this->factory->createStreamFromFile($tmpFilename, 'r');
		Assert::type(Message\Stream::class, $stream);
		Assert::same('file content', (string) $stream);
		Assert::true($stream->isReadable());
	}

	public function testStreamFromResource(): void{
		$resource = fopen('php://memory', 'r+');
		fwrite($resource, 'memory content');

		$stream = $this->factory->createStreamFromResource($resource);

		Assert::type(Message\Stream::class, $stream);
		Assert::same('', $stream->getContents());
		$stream->rewind();
		Assert::same('memory content', $stream->getContents());
		fclose($resource);
	}

	public function testUploadedFile(): void{
		$stream = $this->factory->createStream('uploaded content');
		$uploadedFile = $this->factory->createUploadedFile($stream, null, \UPLOAD_ERR_OK, 'file.txt', 'text/plain');

		Assert::type(Message\UploadedFile::class, $uploadedFile);
		Assert::same(16, $uploadedFile->getSize());
		Assert::same(\UPLOAD_ERR_OK, $uploadedFile->getError());
		Assert::same('file.txt', $uploadedFile->getClientFilename());
		Assert::same('text/plain', $uploadedFile->getClientMediaType());
		Assert::true($uploadedFile->isOk());

		$retrievedStream = $uploadedFile->getStream();
		Assert::same('uploaded content', (string) $retrievedStream);
		Assert::same(16, $retrievedStream->getSize());
		Assert::true($retrievedStream->isReadable());
	}

	public function testUri(): void{
		$uri = $this->factory->createUri('https://user:pass@example.com:8080');

		Assert::type(Message\Uri::class, $uri);
		Assert::same(8080, $uri->getPort());
		Assert::same('example.com', $uri->getHost());
		Assert::same('https://user:pass@example.com:8080', (string) $uri);

		$default = $this->factory->createUri();
		Assert::same('', (string) $default);
	}

}

(new Psr17FactoryTest)->run();
