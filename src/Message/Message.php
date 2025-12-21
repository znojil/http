<?php
declare(strict_types=1);

namespace Znojil\Http\Message;

use Psr\Http\Message\StreamInterface;

abstract class Message implements \Psr\Http\Message\MessageInterface{

	/** @var array<string, string[]> */
	private array $headers = [];

	/** @var array<string, string> mapping: 'content-type' => 'Content-Type' */
	private array $headerNames = [];

	private string $protocol = '1.1';

	private ?StreamInterface $stream = null;

	public function getProtocolVersion(): string{
		return $this->protocol;
	}

	public function withProtocolVersion($version): static{
		$new = clone $this;
		$new->protocol = $version;

		return $new;
	}

	public function getHeaders(): array{
		return $this->headers;
	}

	public function hasHeader($name): bool{
		return isset($this->headerNames[strtolower($name)]);
	}

	public function getHeader($name): array{
		$headerName = strtolower($name);
		if(!isset($this->headerNames[$headerName])){
			return [];
		}

		$originalName = $this->headerNames[$headerName];

		return $this->headers[$originalName];
	}

	public function getHeaderLine($name): string{
		return implode(', ', $this->getHeader($name));
	}

	public function withHeader($name, $value): static{
		$new = clone $this;
		$new->setHeader($name, $value, false);

		return $new;
	}

	public function withAddedHeader($name, $value): static{
		$new = clone $this;
		$new->setHeader($name, $value);

		return $new;
	}

	public function withoutHeader($name): static{
		$new = clone $this;
		$normalized = strtolower($name);
		if(isset($new->headerNames[$normalized])){
			$original = $new->headerNames[$normalized];
			unset($new->headers[$original], $new->headerNames[$normalized]);
		}

		return $new;
	}

	public function getBody(): StreamInterface{
		if($this->stream === null){
			$this->stream = new Stream(\Znojil\Http\Internal\ResourceUtil::tryFopen('php://temp', 'w+'));
		}

		return $this->stream;
	}

	public function withBody(StreamInterface $body): static{
		$new = clone $this;
		$new->stream = $body;

		return $new;
	}

	/**
	 * @param string|string[] $value
	 */
	protected function setHeader(string $name, string|array $value, bool $append = true): void{
		if(!is_array($value)){
			$value = [$value];
		}

		$normalized = strtolower($name);

		if($append && isset($this->headerNames[$normalized])){
			$originalName = $this->headerNames[$normalized];
			$this->headers[$originalName] = array_merge($this->headers[$originalName], $value);
		}else{
			if(isset($this->headerNames[$normalized])){
				unset($this->headers[$this->headerNames[$normalized]]);
			}

			$this->headerNames[$normalized] = $name;
			$this->headers[$name] = $value;
		}
	}

	/**
	 * Specifically for the 'host' header.
	 * @param string|string[] $value
	 * @see https://datatracker.ietf.org/doc/html/rfc7230#section-5.4
	 */
	protected function setHeaderUnshift(string $name, string|array $value): void{
		if(!is_array($value)){
			$value = [$value];
		}

		$normalized = strtolower($name);

		if(isset($this->headerNames[$normalized])){
			unset($this->headers[$this->headerNames[$normalized]]);
			unset($this->headerNames[$normalized]);
		}

		$this->headerNames = [$normalized => $name] + $this->headerNames;
		$this->headers = [$name => $value] + $this->headers;
	}

	protected function setProtocol(string $protocol): void{
		$this->protocol = $protocol;
	}

	protected function setStream(StreamInterface $stream): void{
		$this->stream = $stream;
	}

}
