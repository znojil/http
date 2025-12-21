<?php
declare(strict_types=1);

namespace Znojil\Http\Tests\Fixtures;

final class ResponseFactory implements \Psr\Http\Message\ResponseFactoryInterface{

	public function createResponse(int $code = 200, string $reasonPhrase = ''): Response{
		return new Response($code, reasonPhrase: $reasonPhrase);
	}

}
