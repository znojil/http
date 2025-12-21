<?php
declare(strict_types=1);

namespace Znojil\Http\Tests\Unit;

use Tester\Assert;
use Znojil\Http\Message\ServerRequest;
use Znojil\Http\Message\UploadedFile;

require __DIR__ . '/../bootstrap.php';

/**
 * @testCase
 */
final class MessageServerRequestTest extends \Tester\TestCase{

	public function testFromGlobals(): void{
		$_SERVER['REQUEST_METHOD'] = 'PUT';
		$_SERVER['HTTPS'] = 'on';
		$_SERVER['HTTP_HOST'] = 'api.test.cz';
		$_SERVER['REQUEST_URI'] = '/v1/resource?debug=true';
		$_GET = ['debug' => 'true'];
		$_POST = ['action' => 'update'];
		$_COOKIE = ['token' => 'secret'];
		$_FILES = [
			'doc' => [
				'name' => 'test.txt',
				'type' => 'text/plain',
				'tmp_name' => '/tmp/phpabc',
				'error' => 0,
				'size' => 123
			]
		];

		$serverRequest = ServerRequest::fromGlobals();

		Assert::same('PUT', $serverRequest->getMethod());
		Assert::same('api.test.cz', $serverRequest->getUri()->getHost());
		Assert::same('https://api.test.cz/v1/resource?debug=true', (string) $serverRequest->getUri());
		Assert::same(['token' => 'secret'], $serverRequest->getCookieParams());
		Assert::same(['debug' => 'true'], $serverRequest->getQueryParams());
		Assert::same(['action' => 'update'], $serverRequest->getParsedBody());

		$files = $serverRequest->getUploadedFiles();
		Assert::count(1, $files);
		Assert::type(UploadedFile::class, $files['doc']);
		Assert::same('test.txt', $files['doc']->getClientFilename());
		Assert::same('text/plain', $files['doc']->getClientMediaType());
		Assert::same(123, $files['doc']->getSize());
	}

	public function testConstructor(): void{
		$serverRequest = new ServerRequest('POST', 'https://example.com/api', ['Content-Type' => 'application/json'], 'body content', '1.0', ['REMOTE_ADDR' => '127.0.0.1']);

		Assert::same(['REMOTE_ADDR' => '127.0.0.1'], $serverRequest->getServerParams());
		Assert::same([], $serverRequest->getCookieParams());
		Assert::same([], $serverRequest->getQueryParams());
		Assert::same([], $serverRequest->getUploadedFiles());
		Assert::same([], $serverRequest->getParsedBody());
		Assert::same([], $serverRequest->getAttributes());
		Assert::null($serverRequest->getAttribute('foo'));
		Assert::same('bar', $serverRequest->getAttribute('foo', 'bar'));
	}

	public function testMutability(): void{
		$uploadedFiles = ['file' => new UploadedFile('php://temp', 0)];

		$serverRequest = new ServerRequest('GET', 'http://localhost');
		$newServerRequest = $serverRequest
			->withCookieParams(['theme' => 'dark'])
			->withQueryParams(['q' => 'search'])
			->withUploadedFiles($uploadedFiles)
			->withParsedBody(['foo' => 'bar'])
			->withAttribute('user_name', 'admin')
			->withAttribute('user_id', 58)
			->withoutAttribute('user_name');

		Assert::notSame($serverRequest, $newServerRequest);

		Assert::same([], $serverRequest->getCookieParams());
		Assert::same([], $serverRequest->getQueryParams());
		Assert::same([], $serverRequest->getUploadedFiles());
		Assert::same([], $serverRequest->getParsedBody());
		Assert::same([], $serverRequest->getAttributes());
		Assert::null($serverRequest->getAttribute('user_id'));

		Assert::same(['theme' => 'dark'], $newServerRequest->getCookieParams());
		Assert::same(['q' => 'search'], $newServerRequest->getQueryParams());
		Assert::same($uploadedFiles, $newServerRequest->getUploadedFiles());
		Assert::same(['foo' => 'bar'], $newServerRequest->getParsedBody());
		Assert::same(['user_id' => 58], $newServerRequest->getAttributes());
		Assert::same(58, $newServerRequest->getAttribute('user_id'));
		Assert::null($newServerRequest->getAttribute('user_name'));
	}

}

(new MessageServerRequestTest)->run();
