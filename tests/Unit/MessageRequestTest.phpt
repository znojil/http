<?php
declare(strict_types=1);

namespace Znojil\Http\Tests\Unit;

use Tester\Assert;
use Znojil\Http\Enum\Method;
use Znojil\Http\Message\Request;
use Znojil\Http\Message\Uri;

require __DIR__ . '/../bootstrap.php';

/**
 * @testCase
 */
final class MessageRequestTest extends \Tester\TestCase{

	public function testConstructor(): void{
		$request = new Request('GET', 'https://example.com/path?q=1', [
			'Foo' => 'bar',
			'lorem' => ['ipsum'],
			'foo' => 'Baz'
		], 'hello', '1.0');

		// message
		Assert::same('1.0', $request->getProtocolVersion());
		Assert::same(['Host' => ['example.com'], 'Foo' => ['bar', 'Baz'], 'lorem' => ['ipsum']], $request->getHeaders());
		Assert::true($request->hasHeader('Foo'));
		Assert::true($request->hasHeader('FOO'));
		Assert::true($request->hasHeader('loreM'));
		Assert::same(['bar', 'Baz'], $request->getHeader('Foo'));
		Assert::same(['ipsum'], $request->getHeader('lorem'));
		Assert::same('bar, Baz', $request->getHeaderLine('FOO'));
		Assert::same('hello', (string) $request->getBody());
		// request
		Assert::same('/path?q=1', $request->getRequestTarget());
		Assert::same('GET', $request->getMethod());
		Assert::same('https://example.com/path?q=1', (string) $request->getUri());
	}

	public function testMutability(): void{
		$request = new Request('geT', 'https://example.com/path?q=1', [
			'Foo' => 'bar',
			'lorem' => [
				'ipsum',
				'dolor'
			],
			'bar' => 'foo'
		], null, '1.0');
		$newRequest = $request
			->withProtocolVersion('2.0')
			->withHeader('lorem', 'sit')
			->withHeader('X-Test', 'value')
			->withAddedHeader('foo', 'baz')
			->withoutHeader('bar')
			->withBody(\Znojil\Http\Message\Stream::create('new body'))
			->withRequestTarget('/newpath')
			->withMethod('post')
			->withUri(new Uri('https://example.org/other'));

		// message
		Assert::same('1.0', $request->getProtocolVersion());
		Assert::same(['Host' => ['example.com'], 'Foo' => ['bar'], 'lorem' => ['ipsum', 'dolor'], 'bar' => ['foo']], $request->getHeaders());
		Assert::true($request->hasHeader('Foo'));
		Assert::true($request->hasHeader('lorem'));
		Assert::true($request->hasHeader('BAR'));
		Assert::same(['ipsum', 'dolor'], $request->getHeader('LOREM'));
		Assert::same('ipsum, dolor', $request->getHeaderLine('lorem'));
		Assert::same('', (string) $request->getBody());
		// request
		Assert::same('/path?q=1', $request->getRequestTarget());
		Assert::same('GET', $request->getMethod());
		Assert::same('https://example.com/path?q=1', (string) $request->getUri());

		// message
		Assert::same('2.0', $newRequest->getProtocolVersion());
		Assert::same(['Host' => ['example.org'], 'Foo' => ['bar', 'baz'], 'lorem' => ['sit'], 'X-Test' => ['value']], $newRequest->getHeaders());
		Assert::true($newRequest->hasHeader('Foo'));
		Assert::false($newRequest->hasHeader('BAR'));
		Assert::same(['sit'], $newRequest->getHeader('LOREM'));
		Assert::same('bar, baz', $newRequest->getHeaderLine('foo'));
		Assert::same('new body', (string) $newRequest->getBody());
		// request
		Assert::same('/newpath', $newRequest->getRequestTarget());
		Assert::same('POST', $newRequest->getMethod());
		Assert::same('https://example.org/other', (string) $newRequest->getUri());
	}

	public function testHostHeader(): void{
		$request = new Request('GET', 'https://example.com/path?q=1', ['Host' => 'oldhost.com']);
		$newRequest = $request->withUri(new Uri('https://newhost.org/other'), true);

		Assert::same('newhost.org', $request->withUri(new Uri('https://newhost.org/other'))->getHeaderLine('Host'));
		Assert::same('newhost.org:80', $request->withUri(new Uri('https://newhost.org:80/other'))->getHeaderLine('Host'));
		Assert::same('newhost.org', $request->withUri(new Uri('https://newhost.org:443/other'))->getHeaderLine('Host'));
		Assert::same('oldhost.com', $request->getHeaderLine('Host'));
		Assert::same('oldhost.com', $newRequest->getHeaderLine('Host'));
	}

	public function testRequestTargetPriority(): void{
		Assert::same('/', (new Request('GET', 'https://example.com'))->getRequestTarget());

		$request = new Request('GET', new Uri('https://example.com/foo?bar=baz'));
		$requestCustom = $request->withRequestTarget('*'); // OPTIONS request style

		Assert::same('/foo?bar=baz', $request->getRequestTarget());
		Assert::same('*', $requestCustom->getRequestTarget());
		Assert::same('https://example.com/foo?bar=baz', (string) $requestCustom->getUri());
	}

	public function testEnums(): void{
		$request = new Request(Method::Get, '');

		Assert::same('GET', $request->getMethod());
		Assert::same('POST', $request->withMethod(Method::Post)->getMethod());
	}


}

(new MessageRequestTest)->run();
