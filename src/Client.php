<?php
declare(strict_types=1);

namespace Znojil\Http;

use Psr\Http\Message as PsrMessage;

class Client implements \Psr\Http\Client\ClientInterface{

	private readonly ?Message\Uri $baseUri;

	private readonly PsrMessage\ResponseFactoryInterface $responseFactory;

	/** @var array<int, mixed> */
	private array $defaultCurlOptions = [
		CURLOPT_CONNECTTIMEOUT => 10,
		CURLOPT_FOLLOWLOCATION => true,
		CURLOPT_HEADER => false,
		CURLOPT_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
		CURLOPT_SSL_VERIFYPEER => true,
		CURLOPT_TIMEOUT => 100,
		// CURLOPT_USERAGENT => 'Znojil Http client'
	];

	/**
	 * @param array<string, string|string[]> $defaultHeaders
	 * @param array<int, mixed> $defaultCurlOptions default options for curl_setopt_array() (e.g. CURLOPT_USERAGENT => 'Znojil Http client')
	 */
	public function __construct(
		string|Message\Uri|null $baseUri = null,
		private readonly array $defaultHeaders = [],
		array $defaultCurlOptions = [],
		?PsrMessage\ResponseFactoryInterface $responseFactory = null
	){
		$this->baseUri = $baseUri !== null ? new Message\Uri($baseUri) : null;
		$this->defaultCurlOptions = array_replace($this->defaultCurlOptions, $defaultCurlOptions);
		$this->responseFactory = $responseFactory ?? new Psr17Factory;
	}

	/**
	 * @param array<int, mixed> $curlOptions options for curl_setopt_array() (e.g. CURLOPT_TIMEOUT => 5)
	 */
	public function sendRequest(PsrMessage\RequestInterface $request, array $curlOptions = []): PsrMessage\ResponseInterface{
		if(!($ch = curl_init())){
			throw new Exception\ClientException('Failed to initialize cURL resource.');
		}

		// default headers
		foreach($this->defaultHeaders as $k => $v){
			if(!$request->hasHeader($k)){
				$request = $request->withHeader($k, $v);
			}
		}

		$curlOptions[CURLOPT_CUSTOMREQUEST] = $request->getMethod();

		// url
		if($this->baseUri !== null){
			$request = $request->withUri($this->baseUri->combine($request->getUri()));
		}
		$curlOptions[CURLOPT_URL] = $request->getUri();

		// protocol
		$curlOptions[CURLOPT_HTTP_VERSION] = match($request->getProtocolVersion()){
			'1.0' => CURL_HTTP_VERSION_1_0,
			'2', '2.0' => CURL_HTTP_VERSION_2_0,
			'3', '3.0' => CURL_HTTP_VERSION_3,
			default => CURL_HTTP_VERSION_1_1
		};

		// headers
		$headers = [];
		foreach($request->getHeaders() as $k => $v){
			foreach($v as $v2){
				$headers[] = sprintf('%s: %s', $k, $v2);
			}
		}

		$curlOptions[CURLOPT_HTTPHEADER] = $headers;

		// body
		if($request->getBody()->getSize() > 0 && !array_key_exists(CURLOPT_POSTFIELDS, $curlOptions)){
			$curlOptions[CURLOPT_POSTFIELDS] = (string) $request->getBody();
		}

		// response headers
		$responseHeaders = [];
		$responseVersion = '1.1';
		$responseStatus = 200;
		$responseReason = '';

		$curlOptions[CURLOPT_HEADERFUNCTION] = function (\CurlHandle $ch, string $header) use (&$responseHeaders, &$responseVersion, &$responseStatus, &$responseReason): int{
			$h = trim($header);
			if($h === ''){ // redirect
				return strlen($header);
			}

			if(str_starts_with(strtoupper($h), 'HTTP/')){
				$responseHeaders = [];

				$p = preg_split('~\s+~', $h, 3);
				$responseVersion = substr($p[0] ?? 'HTTP/1.1', 5);
				$responseStatus = (int) ($p[1] ?? 0);
				$responseReason = $p[2] ?? '';
			}else{
				$e = explode(':', $h, 2);
				if(count($e) === 2){
					$responseHeaders[trim($e[0])][] = trim($e[1]);
				}
			}

			return strlen($header);
		};

		// response body
		$curlOptions[CURLOPT_RETURNTRANSFER] = false;

		try{
			$responseBodyStream = new Message\Stream(Internal\ResourceUtil::tryFopen('php://temp', 'w+'));
		}catch(\RuntimeException $e){
			throw new Exception\ClientException('Failed to initialize response body stream (Internal resource error).', previous: $e);
		}

		$curlOptions[CURLOPT_WRITEFUNCTION] = fn(\CurlHandle $ch, string $data): int => $responseBodyStream->write($data);

		// set options
		curl_setopt_array($ch, array_replace($this->defaultCurlOptions, $curlOptions));

		$chResult = curl_exec($ch);
		if($chResult === false){
			$error = curl_error($ch);
			$errorCode = curl_errno($ch);

			throw new Exception\NetworkException($request, $error, $errorCode);
		}

		$responseBodyStream->rewind();

		$response = $this->responseFactory->createResponse($responseStatus === 0 ? (curl_getinfo($ch, CURLINFO_HTTP_CODE) ?: 200) : $responseStatus, $responseReason)
			->withProtocolVersion($responseVersion)
			->withBody($responseBodyStream);

		foreach($responseHeaders as $k => $v){
			$response = $response->withHeader($k, $v);
		}

		return $response;
	}

}
