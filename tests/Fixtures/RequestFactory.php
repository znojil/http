<?php
declare(strict_types=1);

namespace Znojil\Http\Tests\Fixtures;

final class RequestFactory implements \Psr\Http\Message\RequestFactoryInterface{

	public function createRequest(string $method, $uri): Request{
		return new Request($method, $uri);
	}

}
