<?php
declare(strict_types=1);

namespace Znojil\Http\Message;

class Response extends Message implements \Psr\Http\Message\ResponseInterface{

	public const ReasonPhrases = [
		100 => 'Continue',
		101 => 'Switching Protocols',
		102 => 'Processing',
		103 => 'Early Hints',
		200 => 'OK',
		201 => 'Created',
		202 => 'Accepted',
		203 => 'Non-Authoritative Information',
		204 => 'No Content',
		205 => 'Reset Content',
		206 => 'Partial Content',
		207 => 'Multi-Status',
		208 => 'Already Reported',
		226 => 'IM Used',
		300 => 'Multiple Choices',
		301 => 'Moved Permanently',
		302 => 'Found',
		303 => 'See Other',
		304 => 'Not Modified',
		305 => 'Use Proxy',
		307 => 'Temporary Redirect',
		308 => 'Permanent Redirect',
		400 => 'Bad Request',
		401 => 'Unauthorized',
		402 => 'Payment Required',
		403 => 'Forbidden',
		404 => 'Not Found',
		405 => 'Method Not Allowed',
		406 => 'Not Acceptable',
		407 => 'Proxy Authentication Required',
		408 => 'Request Timeout',
		409 => 'Conflict',
		410 => 'Gone',
		411 => 'Length Required',
		412 => 'Precondition Failed',
		413 => 'Payload Too Large',
		414 => 'URI Too Long',
		415 => 'Unsupported Media Type',
		416 => 'Range Not Satisfiable',
		417 => 'Expectation Failed',
		418 => "I'm a teapot",
		421 => 'Misdirected Request',
		422 => 'Unprocessable Entity',
		423 => 'Locked',
		424 => 'Failed Dependency',
		425 => 'Too Early',
		426 => 'Upgrade Required',
		428 => 'Precondition Required',
		429 => 'Too Many Requests',
		431 => 'Request Header Fields Too Large',
		451 => 'Unavailable For Legal Reasons',
		500 => 'Internal Server Error',
		501 => 'Not Implemented',
		502 => 'Bad Gateway',
		503 => 'Service Unavailable',
		504 => 'Gateway Timeout',
		505 => 'HTTP Version Not Supported',
		506 => 'Variant Also Negotiates',
		507 => 'Insufficient Storage',
		508 => 'Loop Detected',
		510 => 'Not Extended',
		511 => 'Network Authentication Required'
	];

	private int $statusCode;

	private string $reasonPhrase;

	/**
	 * @param array<string, string|string[]> $headers
	 * @param string|int|float|bool|resource|\Psr\Http\Message\StreamInterface|null $body
	 */
	public function __construct(
		int $status = 200,
		array $headers = [],
		$body = null,
		string $version = '1.1',
		string $reasonPhrase = ''
	){
		$this->statusCode = $this->filterStatusCode($status);

		foreach($headers as $k => $v){
			$this->setHeader($k, $v);
		}

		$this->setStream(Stream::create($body));

		$this->setProtocol($version);

		$this->reasonPhrase = $this->filterReasonPhrase($reasonPhrase, $this->statusCode);
	}

	public function getStatusCode(): int{
		return $this->statusCode;
	}

	public function withStatus(int $code, string $reasonPhrase = ''): static{
		$statusCode = $this->filterStatusCode($code);
		$reasonPhrase = $this->filterReasonPhrase($reasonPhrase, $statusCode);

		if($statusCode === $this->statusCode && $reasonPhrase === $this->reasonPhrase){
			return $this;
		}

		$new = clone $this;
		$new->statusCode = $statusCode;
		$new->reasonPhrase = $reasonPhrase;

		return $new;
	}

	public function getReasonPhrase(): string{
		return $this->reasonPhrase;
	}

	public function isSuccessful(): bool{
		return $this->statusCode >= 200 && $this->statusCode < 300;
	}

	private function filterStatusCode(int $code): int{
		if($code < 100 || $code > 599){
			throw new \InvalidArgumentException('Invalid HTTP status code: ' . $code);
		}

		return $code;
	}

	private function filterReasonPhrase(string $reason, int $code): string{
		return $reason === ''
			? (self::ReasonPhrases[$code] ?? '')
			: trim($reason);
	}

}
