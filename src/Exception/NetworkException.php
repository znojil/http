<?php
declare(strict_types = 1);

namespace Znojil\Http\Exception;

class NetworkException extends \RuntimeException implements \Psr\Http\Client\NetworkExceptionInterface{

	public function __construct(
		private readonly \Psr\Http\Message\RequestInterface $request,
		string $message = '',
		int $code = 0,
		?\Throwable $previous = null
	){
		parent::__construct($message, $code, $previous);
	}

	public function getRequest(): \Psr\Http\Message\RequestInterface{
		return $this->request;
	}

}
