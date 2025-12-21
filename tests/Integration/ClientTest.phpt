<?php
declare(strict_types=1);

namespace Znojil\Http\Tests\Integration;

use Tester\Assert;
use Znojil\Http\Client;
use Znojil\Http\Exception\NetworkException;
use Znojil\Http\Message\Request;
use Znojil\Http\Tests\Fixtures;
use Znojil\Http\Tests\Support\Server;

require __DIR__ . '/../bootstrap.php';

/**
 * @testCase
 */
final class ClientTest extends \Tester\TestCase{

	private Server $server;

	protected function setUp(): void{
		parent::setUp();
		$this->server = new Server;
		$this->server->start(__DIR__ . '/../Fixtures/Server');
	}

	protected function tearDown(): void{
		parent::tearDown();
		$this->server->stop();
	}

	/**
	 * POST json.
	 */
	public function testSendRequest(): void{
		$ct = \Znojil\Http\Enum\ContentType::Json;

		$client = new Client($this->server->getUrl());

		$data = ['foo' => 'bar'];
		$response = $client->sendRequest(new Request(
			'POST',
			'/json?q=1',
			[
				'Content-Type' => $ct->value,
				'X-Custom' => 'foo'
			],
			json_encode($data)
		));

		Assert::same('3.0', $response->getProtocolVersion());
		Assert::same(200, $response->getStatusCode());
		Assert::same('OK!', $response->getReasonPhrase());
		Assert::same('Bar, baZ', $response->getHeaderLine('foo'));

		$serverReceived = json_decode((string) $response->getBody(), true);
		Assert::same('application/json', $serverReceived['headers']['Content-Type']);
		Assert::same('POST', $serverReceived['method']);
		Assert::same('/json?q=1', $serverReceived['uri']);
		Assert::same('foo', $serverReceived['headers']['X-Custom']);
		Assert::same(json_encode($data), $serverReceived['body']);
		Assert::same(['q' => '1'], $serverReceived['query']);
	}

	public function testNetworkErrors(): void{
		Assert::exception(
			fn(): never => (new Client)->sendRequest(new Request('GET', 'http://invalid.domain')),
			NetworkException::class,
			'Could not resolve host: invalid.domain'
		);

		Assert::exception(
			fn(): never => (new Client($this->server->getUrl()))->sendRequest(new Request('GET', '/sleep?s=2'), [CURLOPT_TIMEOUT => 1]),
			NetworkException::class,
			'~timed out~i'
		);
	}

	public function testNotFound(): void{
		$client = new Client($this->server->getUrl());
		$response = $client->sendRequest(new Request('GET', '/not-found'));

		Assert::same(404, $response->getStatusCode());
		Assert::same('Not Found', $response->getReasonPhrase());
		Assert::same('Endpoint not found', (string) $response->getBody());
	}

	public function testResponseFactory(): void{
		$client = new Client($this->server->getUrl(), responseFactory: new Fixtures\ResponseFactory);
		$response = $client->sendRequest(new Request('GET', '/ping'));

		Assert::type(Fixtures\Response::class, $response);
		Assert::same('2.0', $response->getProtocolVersion());
		Assert::same(200, $response->getStatusCode());
		Assert::same('ok', $response->getReasonPhrase());
	}

}

(new ClientTest)->run();
