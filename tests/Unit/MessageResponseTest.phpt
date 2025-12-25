<?php
declare(strict_types=1);

namespace Znojil\Http\Tests\Unit;

use Tester\Assert;
use Znojil\Http\Message\Response;

require __DIR__ . '/../bootstrap.php';

/**
 * @testCase
 */
final class MessageResponseTest extends \Tester\TestCase{

	public function testConstructor(): void{
		$response = new Response(200, [
			'Foo' => 'bar',
			'lorem' => ['ipsum'],
			'foo' => 'Baz'
		], 'hello', '1.0', 'OK');

		// message
		Assert::same('1.0', $response->getProtocolVersion());
		Assert::same(['Foo' => ['bar', 'Baz'], 'lorem' => ['ipsum']], $response->getHeaders());
		Assert::true($response->hasHeader('Foo'));
		Assert::true($response->hasHeader('FOO'));
		Assert::true($response->hasHeader('loreM'));
		Assert::same(['bar', 'Baz'], $response->getHeader('Foo'));
		Assert::same(['ipsum'], $response->getHeader('lorem'));
		Assert::same('bar, Baz', $response->getHeaderLine('FOO'));
		Assert::same('hello', (string) $response->getBody());
		// response
		Assert::same(200, $response->getStatusCode());
		Assert::same('OK', $response->getReasonPhrase());
		Assert::true($response->isSuccessful());

		Assert::exception(
			fn() => new Response(666),
			\InvalidArgumentException::class,
			'Invalid HTTP status code: 666'
		);
	}

	public function testMutability(): void{
		$response = new Response(201, [
			'Foo' => 'bar',
			'lorem' => [
				'ipsum',
				'dolor'
			],
			'bar' => 'foo'
		], null, '1.0');
		$newResponse = $response
			->withProtocolVersion('2.0')
			->withHeader('lorem', 'sit')
			->withHeader('X-Test', 'value')
			->withAddedHeader('foo', 'baz')
			->withoutHeader('bar')
			->withBody(\Znojil\Http\Message\Stream::create('new body'))
			->withStatus(404, 'not found');

		// message
		Assert::same('1.0', $response->getProtocolVersion());
		Assert::same(['Foo' => ['bar'], 'lorem' => ['ipsum', 'dolor'], 'bar' => ['foo']], $response->getHeaders());
		Assert::true($response->hasHeader('Foo'));
		Assert::true($response->hasHeader('lorem'));
		Assert::true($response->hasHeader('BAR'));
		Assert::same(['ipsum', 'dolor'], $response->getHeader('LOREM'));
		Assert::same('ipsum, dolor', $response->getHeaderLine('lorem'));
		Assert::same('', (string) $response->getBody());
		// response
		Assert::same(201, $response->getStatusCode());
		Assert::same($response::ReasonPhrases[201], $response->getReasonPhrase());
		Assert::true($response->isSuccessful());

		// message
		Assert::same('2.0', $newResponse->getProtocolVersion());
		Assert::same(['Foo' => ['bar', 'baz'], 'lorem' => ['sit'], 'X-Test' => ['value']], $newResponse->getHeaders());
		Assert::true($newResponse->hasHeader('Foo'));
		Assert::false($newResponse->hasHeader('BAR'));
		Assert::same(['sit'], $newResponse->getHeader('LOREM'));
		Assert::same('bar, baz', $newResponse->getHeaderLine('foo'));
		Assert::same('new body', (string) $newResponse->getBody());
		// response
		Assert::same(404, $newResponse->getStatusCode());
		Assert::same('not found', $newResponse->getReasonPhrase());
		Assert::notSame($newResponse::ReasonPhrases[404], $newResponse->getReasonPhrase());
		Assert::false($newResponse->isSuccessful());
	}

}

(new MessageResponseTest)->run();
