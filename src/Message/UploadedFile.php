<?php
declare(strict_types=1);

namespace Znojil\Http\Message;

use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileInterface;
use Znojil\Http\Internal\ResourceUtil;

class UploadedFile implements UploadedFileInterface{

	/**
	 * @return array<string|int, self|array<mixed>>
	 */
	public static function fromGlobals(): array{
		/**
		 * @param array{name: string|array<mixed>, type: string|array<mixed>, tmp_name: string|array<mixed>, error: int|array<mixed>, size: int|array<mixed>} $_f
		 * @return self|array<mixed>
		 */
		$nf = function (array $_f) use (&$nf): array|self{
			if(is_array($_f['tmp_name'])){
				$r = [];
				foreach(array_keys($_f['tmp_name']) as $k){
					/** @var array{name: array<mixed>, type: array<mixed>, tmp_name: array<mixed>, error: array<mixed>, size: array<mixed>} $_f */
					$r[$k] = $nf([
						'tmp_name' => $_f['tmp_name'][$k],
						'size' => $_f['size'][$k],
						'error' => $_f['error'][$k],
						'name' => $_f['name'][$k],
						'type' => $_f['type'][$k],
					]);
				}

				return $r;
			}

			/** @var array{name: string, type: string, tmp_name: string, error: int, size: int} $_f */
			return new self(
				$_f['tmp_name'],
				(int) $_f['size'],
				$_f['error'] ?: \UPLOAD_ERR_OK,
				$_f['name'] ?: null,
				$_f['type'] ?: null
			);
		};

		$files = [];
		foreach($_FILES as $k => $v){
			if(is_array($v) && isset($v['tmp_name'])){
				$files[$k] = $nf($v);
			}
		}

		return $files;
	}

	private ?string $file = null;

	private ?StreamInterface $stream = null;

	private bool $moved = false;

	/**
	 * @param string|resource|StreamInterface|null $streamOrFile
	 */
	public function __construct(
		mixed $streamOrFile,
		private readonly ?int $size,
		private readonly int $error = \UPLOAD_ERR_OK,
		private readonly ?string $clientFilename = null,
		private readonly ?string $clientMediaType = null,
	){
		if(is_string($streamOrFile)){
			$this->file = $streamOrFile;
		}else{
			$this->stream = match(true){
				is_resource($streamOrFile) => new Stream($streamOrFile),
				$streamOrFile instanceof StreamInterface => $streamOrFile,
				default => null,
			};
		}
	}

	public function getStream(): StreamInterface{
		$this->ensureOk();

		if($this->stream !== null){
			return $this->stream;
		}

		if($this->file !== null){
			$this->stream = new Stream(ResourceUtil::tryFopen($this->file, 'r'));
			return $this->stream;
		}

		throw new \RuntimeException('No stream is available for this uploaded file.');
	}

	public function moveTo(string $targetPath): void{
		$this->ensureOk();

		if($targetPath === ''){
			throw new \InvalidArgumentException('Target path must not be empty.');
		}

		if($this->file !== null){
			$success = (php_sapi_name() === 'cli' || !is_uploaded_file($this->file))
				? rename($this->file, $targetPath)
				: move_uploaded_file($this->file, $targetPath);
		}else{
			$stream = $this->getStream();
			if($stream->isSeekable()){
				$stream->rewind();
			}

			$target = ResourceUtil::tryFopen($targetPath, 'w');
			while(!$stream->eof()){
				$data = $stream->read(1048576); // 1MB block
				if($data === ''){
					break;
				}

				fwrite($target, $data);
			}
			fclose($target);

			$success = true;
		}

		if(!$success){
			throw new \RuntimeException("Could not move the file to '$targetPath'.");
		}

		$this->moved = true;
	}

	public function getSize(): ?int{
		return $this->size;
	}

	public function getError(): int{
		return $this->error;
	}

	public function getClientFilename(): ?string{
		return $this->clientFilename;
	}

	public function getClientMediaType(): ?string{
		return $this->clientMediaType;
	}

	public function isOk(): bool{
		return $this->error === \UPLOAD_ERR_OK;
	}

	/**
	 * @throws \RuntimeException when the uploaded file is not valid or has been moved
	 */
	private function ensureOk(): void{
		if(!$this->isOk()){
			throw new \RuntimeException('Uploaded file is not valid due to upload error.');
		}

		if($this->moved){
			throw new \RuntimeException('Uploaded file has already been moved.');
		}
	}

}
