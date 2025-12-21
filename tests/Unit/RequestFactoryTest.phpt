<?php
declare(strict_types=1);

namespace Znojil\Http\Tests\Unit;

use Tester\Assert;
use Znojil\Http\RequestFactory;
use Znojil\Http\Enum\ContentType;
use Znojil\Http\Message\Request;
use Znojil\Http\Tests\Fixtures;

require __DIR__ . '/../bootstrap.php';

/**
 * @testCase
 */
final class RequestFactoryTest extends \Tester\TestCase{

	private RequestFactory $factory;

	protected function setUp(): void{
		parent::setUp();
		$this->factory = new RequestFactory;
	}

	public function testCreateRequest(): void{
		$request = $this->factory->createRequest('get');

		Assert::type(Request::class, $request);
		Assert::same('GET', $request->getMethod());
		Assert::same('', (string) $request->getUri());
		Assert::same('', $request->getHeaderLine('Content-Type'));
		Assert::same([], $request->getHeader('foo'));
		Assert::same('', (string) $request->getBody());
	}

	/**
	 * Query Params.
	 */
	public function testGet(): void{
		$request = $this->factory->get('http://api.com?key=123', ['sort' => 'desc'], ['Foo' => 'bar']);

		Assert::type(Request::class, $request);
		Assert::same('GET', $request->getMethod());
		Assert::same('http://api.com?key=123&sort=desc', (string) $request->getUri());
		Assert::same('', $request->getHeaderLine('Content-Type'));
		Assert::same(['bar'], $request->getHeader('foo'));
		Assert::same('', (string) $request->getBody());
	}

	/**
	 * Form URL Encoded.
	 */
	public function testPost(): void{
		$data = ['foo' => 'bar baz', 'id' => 1];
		$request = $this->factory->post('http://api.com', $data, ['Foo' => 'bar']);

		Assert::type(Request::class, $request);
		Assert::same('POST', $request->getMethod());
		Assert::same(ContentType::Form->value, $request->getHeaderLine('Content-Type'));
		Assert::same(['bar'], $request->getHeader('foO'));
		Assert::same(http_build_query($data), (string) $request->getBody());
	}

	/**
	 * Serialization.
	 */
	public function testPostJson(): void{
		$data = ['name' => 'Jan', 'roles' => [1, 2]];
		$request = $this->factory->postJson('http://api.com/users', $data, ['Foo' => 'bar']);

		Assert::type(Request::class, $request);
		Assert::same('POST', $request->getMethod());
		Assert::same(ContentType::Json->value, $request->getHeaderLine('Content-Type'));
		Assert::same(['bar'], $request->getHeader('Foo'));
		Assert::same(json_encode($data), (string) $request->getBody());
	}

	public function testPatch(): void{
		$data = ['status' => 'active'];
		$request = $this->factory->patch('http://api.com', $data, ['foo' => 'Bar']);

		Assert::type(Request::class, $request);
		Assert::same('PATCH', $request->getMethod());
		Assert::same(ContentType::Form->value, $request->getHeaderLine('Content-Type'));
		Assert::same(['Bar'], $request->getHeader('foo'));
		Assert::same(http_build_query($data), (string) $request->getBody());
	}

	public function testPatchJson(): void{
		$data = ['status' => 'active'];
		$request = $this->factory->patchJson('http://api.com/1', $data, ['foo' => 'bar']);

		Assert::type(Request::class, $request);
		Assert::same('PATCH', $request->getMethod());
		Assert::same(ContentType::Json->value, $request->getHeaderLine('Content-Type'));
		Assert::same(['bar'], $request->getHeader('foo'));
		Assert::same(json_encode($data), (string) $request->getBody());
	}

	public function testPut(): void{
		$data = ['status' => 'active'];
		$request = $this->factory->put('http://api.com', $data, ['foo' => 'Bar']);

		Assert::type(Request::class, $request);
		Assert::same('PUT', $request->getMethod());
		Assert::same(ContentType::Form->value, $request->getHeaderLine('Content-Type'));
		Assert::same(['Bar'], $request->getHeader('foo'));
		Assert::same(http_build_query($data), (string) $request->getBody());
	}

	public function testPutJson(): void{
		$data = ['status' => 'active'];
		$request = $this->factory->putJson('http://api.com/1', $data, ['foo' => 'bar']);

		Assert::type(Request::class, $request);
		Assert::same('PUT', $request->getMethod());
		Assert::same(ContentType::Json->value, $request->getHeaderLine('Content-Type'));
		Assert::same(['bar'], $request->getHeader('foo'));
		Assert::same(json_encode($data), (string) $request->getBody());
	}

	public function testDelete(): void{
		$request = $this->factory->delete('http://api.com/1', ['force' => 'true'], ['Foo' => 'BAR']);

		Assert::type(Request::class, $request);
		Assert::same('DELETE', $request->getMethod());
		Assert::same('http://api.com/1?force=true', (string) $request->getUri());
		Assert::same('', $request->getHeaderLine('Content-Type'));
		Assert::same(['BAR'], $request->getHeader('FOO'));
		Assert::same('', (string) $request->getBody());
	}

	public function testWithCustomRequestFactory(): void{
		$factory = new RequestFactory(new Fixtures\RequestFactory);
		$request = $factory->createRequest('get');

		Assert::type(Fixtures\Request::class, $request);
		Assert::same('GET', $request->getMethod());
	}

	public function testManualObjectBody(): void{
		$object = new \stdClass;
		$object->foo = 'bar';
		$objectJson = json_encode($object);

		$request = $this->factory->post('http://api.com', $object);
		Assert::type(Request::class, $request);
		Assert::same(ContentType::Json->value, $request->getHeaderLine('Content-Type'));
		Assert::same($objectJson, (string) $request->getBody());
	}

	/**
	 * Scalar.
	 */
	public function testManualStringBody(): void{
		$xml = '<root>test</root>';
		$request = $this->factory->post('http://api.com', $xml, ['Content-Type' => 'application/xml']);

		Assert::type(Request::class, $request);
		Assert::same('application/xml', $request->getHeaderLine('Content-Type'));
		Assert::same($xml, (string) $request->getBody());
	}

	public function testManualResourceBody(): void{
		$data = 'test data';
		$resource = \Znojil\Http\Internal\ResourceUtil::tryFopen('php://memory', 'r+');
		fwrite($resource, $data);
		rewind($resource);

		$request = $this->factory->post('http://api.com', $resource, ['Content-Type' => ContentType::Plain->value]);

		Assert::type(Request::class, $request);
		Assert::same('text/plain', $request->getHeaderLine('Content-Type'));
		Assert::same($data, fread($resource, strlen($data)));

		fclose($resource);
	}

}

(new RequestFactoryTest)->run();
