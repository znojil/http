<?php
declare(strict_types=1);

namespace Znojil\Http\Tests\Cases;

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

		/** @var array{method: string, uri: string, query: array<string, string>, headers: array<string, string>, body: string} */
		$serverReceived = json_decode((string) $response->getBody(), true);
		Assert::same('application/json', $serverReceived['headers']['Content-Type']);
		Assert::same('POST', $serverReceived['method']);
		Assert::same('/json?q=1', $serverReceived['uri']);
		Assert::same('foo', $serverReceived['headers']['X-Custom']);
		Assert::same(json_encode($data), $serverReceived['body']);
		Assert::same(['q' => '1'], $serverReceived['query']);
	}

	/**
	 * HEAD nobody.
	 */
	public function testHeadRequest(): void{
		$client = new Client($this->server->getUrl());

		$response = $client->sendRequest(
			new Request(\Znojil\Http\Enum\Method::Head, '/ping'),
			[CURLOPT_TIMEOUT => 3]
		);

		Assert::same(200, $response->getStatusCode());
		Assert::same('4', $response->getHeaderLine('Content-Length'));
		Assert::same('', (string) $response->getBody());
	}

	public function testNetworkErrors(): void{
		Assert::exception(
			fn() => (new Client)->sendRequest(new Request('GET', 'http://invalid.domain')),
			NetworkException::class,
			'Could not resolve host: invalid.domain'
		);

		Assert::exception(
			fn() => (new Client($this->server->getUrl()))->sendRequest(new Request('GET', '/sleep?s=2'), [CURLOPT_TIMEOUT => 1]),
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

	public function testDefaultHeaders(): void{
		$client = new Client($this->server->getUrl(), defaultHeaders: [
			'X-Api-Key' => 'secret123',
			'Accept' => 'application/json'
		]);

		$response = $client->sendRequest(new Request('POST', '/json'));
		/** @var array{headers: array<string, string>} */
		$serverReceived = json_decode((string) $response->getBody(), true);
		Assert::same('secret123', $serverReceived['headers']['X-Api-Key']);
		Assert::same('application/json', $serverReceived['headers']['Accept']);

		$response2 = $client->sendRequest(new Request('POST', '/json', ['Accept' => 'text/html']));
		/** @var array{headers: array<string, string>} */
		$serverReceived2 = json_decode((string) $response2->getBody(), true);
		Assert::same('secret123', $serverReceived2['headers']['X-Api-Key']);
		Assert::same('text/html', $serverReceived2['headers']['Accept']);
	}

	public function testBaseUriCombining(): void{
		$client = new Client($this->server->getUrl() . '/api/v1?token=abc');

		$response = $client->sendRequest(new Request('POST', 'users?limit=10'));

		/** @var array{uri: string, query: array<string, string>} */
		$serverReceived = json_decode((string) $response->getBody(), true);
		Assert::same('/api/v1/users?token=abc&limit=10', $serverReceived['uri']);
		Assert::same('abc', $serverReceived['query']['token']);
		Assert::same('10', $serverReceived['query']['limit']);
	}

}

(new ClientTest)->run();
