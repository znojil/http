<?php
declare(strict_types=1);

namespace Znojil\Http\Message;

use Psr\Http\Message\StreamInterface;

class Stream implements StreamInterface{

	/**
	 * @param string|int|float|bool|resource|StreamInterface|null $body
	 * @throws \InvalidArgumentException if the body type is invalid
	 */
	public static function create($body = ''): static|StreamInterface{
		if(is_resource($body)){
			return new self($body);
		}

		if($body instanceof StreamInterface){
			return $body;
		}

		$body = $body ?? '';
		if(is_scalar($body)){
			$stream = new self(\Znojil\Http\Internal\ResourceUtil::tryFopen('php://temp', 'w+'));
			if($body !== ''){
				$stream->write((string) $body);
				$stream->seek(0);
			}

			return $stream;
		}

		throw new \InvalidArgumentException('Invalid body type for request: ' . gettype($body));
	}

	private const ReadableModes = '~r|a\+|ab\+|w\+|wb\+|x\+|xb\+|c\+|cb\+~';

	private const WritableModes = '~a|w|r\+|rb\+|rw|x|c~';

	/** @var ?resource */
	private $resource;

	private bool $seekable;

	private bool $writable;

	private bool $readable;

	/**
	 * @param resource $resource
	 */
	public function __construct($resource){
		if(!is_resource($resource)){
			throw new \InvalidArgumentException('Stream needs a valid resource.');
		}

		$meta = stream_get_meta_data($resource);

		$this->resource = $resource;
		$this->seekable = $meta['seekable'];
		$this->writable = preg_match(self::WritableModes, $meta['mode']) === 1;
		$this->readable = preg_match(self::ReadableModes, $meta['mode']) === 1;
	}

	public function __toString(): string{
		try{
			if($this->isSeekable()){
				$this->rewind();
			}

			return $this->getContents();
		}catch(\Throwable){
			return '';
		}
	}

	public function close(): void{
		if($this->resource !== null){
			fclose($this->resource);
		}

		$this->detach();
	}

	public function detach(){
		$resource = $this->resource;

		$this->resource = null;
		$this->readable= $this->writable = $this->seekable = false;

		return $resource;
	}

	public function getSize(): ?int{
		if(!is_resource($this->resource)){
			return null;
		}

		$stats = fstat($this->resource);

		return $stats['size'] ?? null;
	}

	public function tell(): int{
		if(!is_resource($this->resource)){
			throw new \RuntimeException('Stream is detached.');
		}

		$position = ftell($this->resource);
		if($position === false){
			throw new \RuntimeException('Unable to determine stream position.');
		}

		return $position;
	}

	public function eof(): bool{
		return !is_resource($this->resource) || feof($this->resource);
	}

	public function isSeekable(): bool{
		return $this->seekable;
	}

	public function seek(int $offset, int $whence = SEEK_SET): void{
		if(!is_resource($this->resource)){
			throw new \RuntimeException('Stream is detached.');
		}

		if(!$this->isSeekable()){
			throw new \RuntimeException('Cannot seek in a non-seekable stream.');
		}

		if(fseek($this->resource, $offset, $whence) === -1){
			throw new \RuntimeException("Unable to seek to stream position '$offset' with whence '$whence'.");
		}
	}

	public function rewind(): void{
		$this->seek(0);
	}

	public function isWritable(): bool{
		return $this->writable;
	}

	public function write(string $string): int{
		if(!is_resource($this->resource)){
			throw new \RuntimeException('Stream is detached.');
		}

		if(!$this->isWritable()){
			throw new \RuntimeException('Cannot write to non-writable stream.');
		}

		$bytesWritten = fwrite($this->resource, $string);
		if($bytesWritten === false){
			throw new \RuntimeException('Unable to write to stream.');
		}

		return $bytesWritten;
	}

	public function isReadable(): bool{
		return $this->readable;
	}

	public function read(int $length): string{
		if(!is_resource($this->resource)){
			throw new \RuntimeException('Stream is detached.');
		}

		if(!$this->isReadable()){
			throw new \RuntimeException('Cannot read from non-readable stream.');
		}

		if($length < 0){
			throw new \RuntimeException('Length parameter cannot be negative.');
		}

		if($length === 0){
			return '';
		}

		$data = fread($this->resource, $length);
		if($data === false){
			throw new \RuntimeException('Unable to read from stream.');
		}

		return $data;
	}

	public function getContents(): string{
		if(!is_resource($this->resource)){
			throw new \RuntimeException('Stream is detached.');
		}

		if(!$this->isReadable()){
			throw new \RuntimeException('Cannot read from non-readable stream.');
		}

		$contents = stream_get_contents($this->resource);
		if($contents === false){
			throw new \RuntimeException('Unable to read from stream.');
		}

		return $contents;
	}

	public function getMetadata(?string $key = null): mixed{
		if(!is_resource($this->resource)){
			return $key === null ? [] : null;
		}

		$meta = stream_get_meta_data($this->resource);
		if($key === null){
			return $meta;
		}

		return $meta[$key] ?? null;
	}

}
