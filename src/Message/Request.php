<?php
declare(strict_types=1);

namespace Znojil\Http\Message;

use Psr\Http\Message\UriInterface;
use Znojil\Http\Enum\Method;

class Request extends Message implements \Psr\Http\Message\RequestInterface{

	private string $method;

	private ?string $requestTarget = null;

	private UriInterface $uri;

	/**
	 * @param array<string, string|string[]> $headers
	 * @param string|int|float|bool|resource|\Psr\Http\Message\StreamInterface|null $body
	 */
	public function __construct(
		string|Method $method,
		string|UriInterface $uri,
		array $headers = [],
		$body = null,
		string $version = '1.1'
	){
		$this->method = $this->filterMethod($method);

		$this->uri = is_string($uri) ? new Uri($uri) : $uri;

		foreach($headers as $k => $v){
			$this->setHeader($k, $v);
		}

		if(!$this->hasHeader('host')){
			$this->updateHostFromUri();
		}

		$this->setStream(Stream::create($body));

		$this->setProtocol($version);
	}

	public function getRequestTarget(): string{
		if($this->requestTarget !== null){
			return $this->requestTarget;
		}

		if(($target = $this->uri->getPath()) === ''){
			$target = '/';
		}

		if(($query = $this->uri->getQuery()) !== ''){
			$target .= '?' . $query;
		}

		return $target;
	}

	public function withRequestTarget(string $requestTarget): static{
		if($requestTarget === $this->requestTarget){
			return $this;
		}

		$new = clone $this;
		$new->requestTarget = $requestTarget;

		return $new;
	}

	public function getMethod(): string{
		return $this->method;
	}

	public function withMethod(string|Method $method): static{
		$method = $this->filterMethod($method);
		if($method === $this->method){
			return $this;
		}

		$new = clone $this;
		$new->method = $method;

		return $new;
	}

	public function getUri(): UriInterface{
		return $this->uri;
	}

	public function withUri(UriInterface $uri, bool $preserveHost = false): static{
		$new = clone $this;
		$new->uri = $uri;

		if(!$preserveHost || !$this->hasHeader('host')){
			$new->updateHostFromUri();
		}

		return $new;
	}

	private function updateHostFromUri(): void{
		if(($host = $this->uri->getHost()) === ''){
			return;
		}

		if(($port = $this->uri->getPort()) !== null){
			$host .= ':' . $port;
		}

		$this->setHeaderUnshift('Host', $host);
	}

	private function filterMethod(string|Method $method): string{
		return $method instanceof Method ? $method->value : strtoupper($method);
	}

}
