<?php
declare(strict_types=1);

namespace Znojil\Http;

use Psr\Http\Message as PsrMessage;

class Psr17Factory implements PsrMessage\RequestFactoryInterface, PsrMessage\ResponseFactoryInterface, PsrMessage\ServerRequestFactoryInterface, PsrMessage\StreamFactoryInterface, PsrMessage\UploadedFileFactoryInterface, PsrMessage\UriFactoryInterface{

	public function createRequest(string $method, $uri): Message\Request{
		return new Message\Request($method, $uri);
	}

	public function createResponse(int $code = 200, string $reasonPhrase = ''): Message\Response{
		return new Message\Response($code, reasonPhrase: $reasonPhrase);
	}

	/**
	 * @param array<mixed> $serverParams
	 */
	public function createServerRequest(string $method, $uri, array $serverParams = []): Message\ServerRequest{
		return new Message\ServerRequest($method, $uri, serverParams: $serverParams);
	}

	public function createStream(string $content = ''): Message\Stream|PsrMessage\StreamInterface{
		return Message\Stream::create($content);
	}

	public function createStreamFromFile(string $filename, string $mode = 'r'): Message\Stream{
		return new Message\Stream(Internal\ResourceUtil::tryFopen($filename, $mode));
	}

	public function createStreamFromResource($resource): Message\Stream{
		return new Message\Stream($resource);
	}

	public function createUploadedFile(
		PsrMessage\StreamInterface $stream,
		?int $size = null,
		int $error = \UPLOAD_ERR_OK,
		?string $clientFilename = null,
		?string $clientMediaType = null
	): Message\UploadedFile{
		return new Message\UploadedFile($stream, $size ?? $stream->getSize(), $error, $clientFilename, $clientMediaType);
	}

	public function createUri(string $uri = ''): Message\Uri{
		return new Message\Uri($uri);
	}

}
