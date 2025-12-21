<?php
declare(strict_types=1);

namespace Znojil\Http;

use Psr\Http\Message\RequestFactoryInterface ;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\UriInterface;

class RequestFactory{

	private readonly RequestFactoryInterface $requestFactory;

	public function __construct(
		?RequestFactoryInterface $requestFactory = null
	){
		$this->requestFactory = $requestFactory ?? new Psr17Factory;
	}

	/**
	 * @param array<string, string|string[]> $headers
	 * @throws \LogicException
	 */
	public function createRequest(string|Enum\Method $method, string|UriInterface $uri = '', array $headers = [], mixed $body = null): RequestInterface{
		$method = $method instanceof Enum\Method ? $method->value : $method;

		if($body !== null){
			if(($body = $this->prepareBody($body, $headers)) === false){
				throw new \LogicException('Failed to prepare the query body.');
			}
		}
		$stream = Message\Stream::create($body);

		$request = $this->requestFactory->createRequest($method, $uri)
			->withBody($stream);

		foreach($headers as $k => $v){
			$request = $request->withHeader($k, $v);
		}

		return $request;
	}

	/**
	 * @param array<int|string, mixed> $queryParams
	 * @param array<string, string|string[]> $headers
	 */
	public function get(string|UriInterface $uri = '', array $queryParams = [], array $headers = []): RequestInterface{
		if(!empty($queryParams)){
			$newQuery = http_build_query($queryParams, '', '&', PHP_QUERY_RFC3986);

			$uriObj = $uri instanceof UriInterface ? $uri : new Message\Uri($uri);
			$currentQuery = $uriObj->getQuery();

			$uri = $uriObj->withQuery(($currentQuery = $uriObj->getQuery()) !== ''
				? $currentQuery . '&' . $newQuery
				: $newQuery
			);
		}

		return $this->createRequest(Enum\Method::Get, $uri, $headers);
	}

	/**
	 * @param array<string, string|string[]> $headers
	 */
	public function post(string|UriInterface $uri = '', mixed $body = null, array $headers = []): RequestInterface{
		return $this->createRequest(Enum\Method::Post, $uri, $headers, $body);
	}

	/**
	 * @param array<mixed>|object $data
	 * @param array<string, string|string[]> $headers
	 */
	public function postJson(string|UriInterface $uri = '', array|object $data = [], array $headers = []): RequestInterface{
		$headers['Content-Type'] = Enum\ContentType::Json->value;
		return $this->createRequest(Enum\Method::Post, $uri, $headers, $data);
	}

	/**
	 * @param array<string, string|string[]> $headers
	 */
	public function patch(string|UriInterface $uri = '', mixed $body = null, array $headers = []): RequestInterface{
		return $this->createRequest(Enum\Method::Patch, $uri, $headers, $body);
	}

	/**
	 * @param array<mixed>|object $data
	 * @param array<string, string|string[]> $headers
	 */
	public function patchJson(string|UriInterface $uri = '', array|object $data = [], array $headers = []): RequestInterface{
		$headers['Content-Type'] = Enum\ContentType::Json->value;
		return $this->createRequest(Enum\Method::Patch, $uri, $headers, $data);
	}

	/**
	 * @param array<string, string|string[]> $headers
	 */
	public function put(string|UriInterface $uri = '', mixed $body = null, array $headers = []): RequestInterface{
		return $this->createRequest(Enum\Method::Put, $uri, $headers, $body);
	}

	/**
	 * @param array<mixed>|object $data
	 * @param array<string, string|string[]> $headers
	 */
	public function putJson(string|UriInterface $uri = '', array|object $data = [], array $headers = []): RequestInterface{
		$headers['Content-Type'] = Enum\ContentType::Json->value;
		return $this->createRequest(Enum\Method::Put, $uri, $headers, $data);
	}

	/**
	 * @param array<int|string, mixed> $queryParams
	 * @param array<string, string|string[]> $headers
	 */
	public function delete(string|UriInterface $uri = '', array $queryParams = [], array $headers = []): RequestInterface{
		return $this->get($uri, $queryParams, $headers)
			->withMethod(Enum\Method::Delete->value);
	}

	/**
	 * @param array<string, string|string[]> &$headers
	 * @return string|resource|false|null
	 * @throws \InvalidArgumentException when body cannot be prepared
	 */
	private function prepareBody(mixed $body, array &$headers): mixed{
		$contentType = array_change_key_case($headers, CASE_LOWER)['content-type'] ?? '';
		if(is_array($contentType)){
			$contentType = implode(', ', $contentType); // line
		}

		switch(true){
			case is_array($body):
				if($this->arrayHasCurlFile($body)){
					throw new \InvalidArgumentException("Cannot prepare body with CURLFile, use raw 'CURLOPT_POSTFIELDS'.");
				}elseif(str_contains($contentType, Enum\ContentType::Json->value)){
					return json_encode($body);
				}else{
					if($contentType === ''){
						$headers['Content-Type'] = Enum\ContentType::Form->value;
					}

					return http_build_query($body);
				}
			case is_object($body):
				if(!str_contains($contentType, Enum\ContentType::Json->value)){
					$headers['Content-Type'] = Enum\ContentType::Json->value;
				}

				return json_encode($body);
			case is_scalar($body):
				return (string) $body;
			case is_resource($body):
				return $body;
			default:
				return null;
		};
	}

	/**
	 * @param array<mixed> $array
	 */
	private function arrayHasCurlFile(array $array): bool{
		foreach($array as $v){
			if(
				$v instanceof \CURLFile
				|| (is_array($v) && $this->arrayHasCurlFile($v))
			){
				return true;
			}
		}

		return false;
	}

}
