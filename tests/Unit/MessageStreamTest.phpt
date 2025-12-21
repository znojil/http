<?php
declare(strict_types=1);

namespace Znojil\Http\Tests\Unit;

use Tester\Assert;
use Znojil\Http\Message\Stream;

require __DIR__ . '/../bootstrap.php';

/**
 * @testCase
 */
final class MessageStreamTest extends \Tester\TestCase{

	public function testCreateFromResource(): void{
		$resource = \Znojil\Http\Internal\ResourceUtil::tryFopen('php://memory', 'r+');
		fwrite($resource, 'test data');
		rewind($resource);

		$stream = Stream::create($resource);
		Assert::same('test data', $stream->getContents());
		Assert::same('test data', (string) $stream);

		fclose($resource);
	}

	public function testCreateFromStream(): void{
		$stream1 = Stream::create('hello stream');
		$stream2 = Stream::create($stream1);

		Assert::same('hello stream', $stream1->getContents());
		Assert::same('', $stream2->getContents());
		Assert::same($stream1, $stream2);
		Assert::same('hello stream', (string) $stream2);
	}

	public function testCreateFromString(): void{
		$stream = Stream::create('hello world');

		Assert::true($stream->isReadable());
		Assert::true($stream->isWritable());
		Assert::true($stream->isSeekable());

		Assert::same(11, $stream->getSize());
		Assert::same(0, $stream->tell());
		Assert::same('hello world', (string) $stream);

		Assert::same(11, $stream->getSize());
		Assert::same(11, $stream->tell());
		Assert::same('', $stream->getContents());
	}

	public function testReadonly(): void{
		$file = __DIR__ . '/temp_read.txt';
		file_put_contents($file, 'readonly content');

		$resource = fopen($file, 'r');
		$stream = new Stream($resource);

		Assert::true($stream->isReadable());
		Assert::false($stream->isWritable());
		Assert::true($stream->isSeekable());

		Assert::exception(function() use ($stream): void{
			$stream->write('fail');
		}, \RuntimeException::class, 'Cannot write to non-writable stream.');

		$stream->close();
		@unlink($file);
	}

	public function testWriteonly(): void{
		$file = __DIR__ . '/temp_write.txt';

		$resource = fopen($file, 'w');
		$stream = new Stream($resource);

		Assert::false($stream->isReadable());
		Assert::true($stream->isWritable());
		Assert::true($stream->isSeekable());

		Assert::exception(function() use ($stream): void{
			$stream->read(10);
		}, \RuntimeException::class, 'Cannot read from non-readable stream.');

		$stream->close();
		@unlink($file);
	}

	public function testDetach(): void{
		$stream = Stream::create('persist');

		Assert::true($stream->isReadable());

		$resource = $stream->detach();
		Assert::type('resource', $resource);

		Assert::false($stream->isReadable());
		Assert::false($stream->isWritable());
		Assert::false($stream->isSeekable());
		Assert::null($stream->getSize());

		Assert::exception(function() use ($stream): void{
			$stream->getContents();
		}, \RuntimeException::class, 'Stream is detached.');

		fclose($resource);
	}

	public function testSeekAndRewind(): void{
		$stream = Stream::create('12345');

		$stream->seek(2);
		Assert::same(2, $stream->tell());
		Assert::same('345', $stream->getContents());
		Assert::same('', $stream->getContents());

		$stream->rewind();
		Assert::same(0, $stream->tell());
		Assert::same('12345', $stream->getContents());
		Assert::same('', $stream->getContents());
	}

	public function testReadWithZeroAndNegativeLength(): void{
		$stream = Stream::create('abcdef');

		Assert::same('', $stream->read(0));

		Assert::exception(function() use ($stream): void{
			$stream->read(-1);
		}, \RuntimeException::class, 'Length parameter cannot be negative.');
	}

}

(new MessageStreamTest)->run();
