<?php
declare(strict_types=1);

namespace Znojil\Http\Message;

use Psr\Http\Message as PsrMessage;

class ServerRequest extends Request implements PsrMessage\ServerRequestInterface{

	public static function fromGlobals(): self{
		if(isset($_SERVER['REQUEST_METHOD']) && is_string($_SERVER['REQUEST_METHOD'])){
			$requestMethod = $_SERVER['REQUEST_METHOD'];
		}

		$headers = [];
		if(function_exists('getallheaders')){
			$headers = getallheaders();
		}else{
			foreach($_SERVER as $k => $v){
				if(str_starts_with($k, 'HTTP_')){
					$headers[str_replace('_', '-', substr($k, 5))] = $v;
				}
			}
		}

		if(isset($_SERVER['SERVER_PROTOCOL']) && is_string($_SERVER['SERVER_PROTOCOL'])){
			$serverProtocol = $_SERVER['SERVER_PROTOCOL'];
		}

		/** @var array<string, string|string[]> $headers */

		$serverRequest = new self(
			$requestMethod ?? 'GET',
			Uri::fromGlobals(),
			$headers,
			fopen('php://input', 'r'),
			isset($serverProtocol) ? substr(strtoupper($serverProtocol), 5) : '1.1',
			$_SERVER
		);

		return $serverRequest
			->withCookieParams($_COOKIE)
			->withQueryParams($_GET)
			->withUploadedFiles(UploadedFile::fromGlobals())
			->withParsedBody($_POST);
	}

	/** @var array<mixed> */
	private array $cookieParams = [];

	/** @var array<mixed> */
	private array $queryParams = [];

	/** @var array<mixed> */
	private array $uploadedFiles = [];

	/** @var null|array<mixed>|object */
	private null|array|object $parsedBody = [];

	/** @var array<mixed> */
	private array $attributes = [];

	/**
	 * @param array<string, string|string[]> $headers
	 * @param string|int|float|bool|resource|\Psr\Http\Message\StreamInterface|null $body
	 * @param array<mixed> $serverParams
	 */
	public function __construct(
		string|\Znojil\Http\Enum\Method $method,
		string|PsrMessage\UriInterface $uri,
		array $headers = [],
		$body = null,
		string $version = '1.1',
		private readonly array $serverParams = []
	){
		parent::__construct($method, $uri, $headers, $body, $version);
	}

	/**
	 * @return array<mixed>
	 */
	public function getServerParams(): array{
		return $this->serverParams;
	}

	/**
	 * @return array<mixed>
	 */
	public function getCookieParams(): array{
		return $this->cookieParams;
	}

	/**
	 * @param array<mixed> $cookies
	 */
	public function withCookieParams(array $cookies): static{
		$new = clone $this;
		$new->cookieParams = $cookies;

		return $new;
	}

	/**
	 * @return array<mixed>
	 */
	public function getQueryParams(): array{
		return $this->queryParams;
	}

	/**
	 * @param array<mixed> $queryParams
	 */
	public function withQueryParams(array $queryParams): static{
		$new = clone $this;
		$new->queryParams = $queryParams;

		return $new;
	}

	/**
	 * @return array<mixed>
	 */
	public function getUploadedFiles(): array{
		return $this->uploadedFiles;
	}

	/**
	 * @param array<mixed> $uploadedFiles
	 */
	public function withUploadedFiles(array $uploadedFiles): static{
		$new = clone $this;
		$new->uploadedFiles = $uploadedFiles;

		return $new;
	}

	/**
	 * @return null|array<mixed>|object
	 */
	public function getParsedBody(): null|array|object{
		return $this->parsedBody;
	}

	/**
	 * @param null|array<mixed>|object $data
	 */
	public function withParsedBody($data): static{
		$new = clone $this;
		$new->parsedBody = $data;

		return $new;
	}

	/**
	 * @return array<mixed>
	 */
	public function getAttributes(): array{
		return $this->attributes;
	}

	public function getAttribute(string $name, mixed $default = null): mixed{
		return array_key_exists($name, $this->attributes) ? $this->attributes[$name] : $default;
	}

	public function withAttribute(string $name, mixed $value): static{
		$new = clone $this;
		$new->attributes[$name] = $value;

		return $new;
	}

	public function withoutAttribute(string $name): static{
		if(!array_key_exists($name, $this->attributes)){
			return $this;
		}

		$new = clone $this;
		unset($new->attributes[$name]);

		return $new;
	}

}
