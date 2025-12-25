<?php
declare(strict_types=1);

namespace Znojil\Http\Tests\Unit;

use Tester\Assert;
use Znojil\Http\Message\Stream;
use Znojil\Http\Message\UploadedFile;

require __DIR__ . '/../bootstrap.php';

/**
 * @testCase
 */
final class MessageUploadedFileTest extends \Tester\TestCase{

	public function testFromGlobals(): void{
		$tmpName1 = TempDir . '/temp_source1.txt';
		file_put_contents($tmpName1, 'tmp file content 1');
		$tmpName2 = TempDir . '/temp_source2.txt';
		file_put_contents($tmpName2, 'Tmp file content 2.');
		$tmpName3 = TempDir . '/temp_source3.txt';
		file_put_contents($tmpName3, 'tmp file content 33');

		$_FILES = [
			'uploadFile1' => [
				'name' => 'MyFile1.txt',
				'type' => 'text/plain',
				'tmp_name' => $tmpName1,
				'error' => \UPLOAD_ERR_OK,
				'size' => filesize($tmpName1)
			],
			'uploadFile2' => [
				'name' => [
					0 => '',
					1 => 'MyFile2.txt'
				],
				'type' => [
					0 => '',
					1 => 'text'
				],
				'tmp_name' => [
					0 => '',
					1 => $tmpName2
				],
				'error' => [
					0 => \UPLOAD_ERR_NO_FILE,
					1 => \UPLOAD_ERR_OK
				],
				'size' => [
					0 => 0,
					1 => filesize($tmpName2)
				],
			],
			'uploadFile3' => [
				'name' => [
					1 => 'MyFile3.txt'
				],
				'type' => [
					1 => 'plain'
				],
				'tmp_name' => [
					1 => $tmpName3
				],
				'error' => [
					1 => \UPLOAD_ERR_OK
				],
				'size' => [
					1 => filesize($tmpName3)
				],
			]
		];
		$file = UploadedFile::fromGlobals();

		Assert::same('tmp file content 1', (string) $file['uploadFile1']->getStream());
		Assert::same(filesize($tmpName1), $file['uploadFile1']->getSize());
		Assert::same(\UPLOAD_ERR_OK, $file['uploadFile1']->getError());
		Assert::same('MyFile1.txt', $file['uploadFile1']->getClientFilename());
		Assert::same('text/plain', $file['uploadFile1']->getClientMediaType());
		Assert::true($file['uploadFile1']->isOk());

		Assert::exception(
			fn() => $file['uploadFile2'][0]->getStream(),
			\RuntimeException::class,
			'Uploaded file is not valid due to upload error.'
		);
		Assert::same(0, $file['uploadFile2'][0]->getSize());
		Assert::same(\UPLOAD_ERR_NO_FILE, $file['uploadFile2'][0]->getError());
		Assert::same(null, $file['uploadFile2'][0]->getClientFilename());
		Assert::same(null, $file['uploadFile2'][0]->getClientMediaType());
		Assert::false($file['uploadFile2'][0]->isOk());

		Assert::same('Tmp file content 2.', (string) $file['uploadFile2'][1]->getStream());
		Assert::same(filesize($tmpName2), $file['uploadFile2'][1]->getSize());
		Assert::same(\UPLOAD_ERR_OK, $file['uploadFile2'][1]->getError());
		Assert::same('MyFile2.txt', $file['uploadFile2'][1]->getClientFilename());
		Assert::same('text', $file['uploadFile2'][1]->getClientMediaType());
		Assert::true($file['uploadFile2'][1]->isOk());

		Assert::same('tmp file content 33', (string) $file['uploadFile3'][1]->getStream());
		Assert::same(filesize($tmpName3), $file['uploadFile3'][1]->getSize());
		Assert::same(\UPLOAD_ERR_OK, $file['uploadFile3'][1]->getError());
		Assert::same('MyFile3.txt', $file['uploadFile3'][1]->getClientFilename());
		Assert::same('plain', $file['uploadFile3'][1]->getClientMediaType());
		Assert::true($file['uploadFile3'][1]->isOk());
	}

	public function testConstructor(): void{
		$stream = Stream::create('uploaded file content');
		$file = new UploadedFile($stream, $stream->getSize(), \UPLOAD_ERR_OK, 'example.txt', 'text/plain');

		Assert::same($stream, $file->getStream());
		Assert::same($stream->getSize(), $file->getSize());
		Assert::same(UPLOAD_ERR_OK, $file->getError());
		Assert::same('example.txt', $file->getClientFilename());
		Assert::same('text/plain', $file->getClientMediaType());
		Assert::true($file->isOk());
	}

	public function testMoveTo(): void{
		$source = TempDir . '/temp_source.txt';
		$target = TempDir . '/temp_target.txt';
		file_put_contents($source, 'file content');

		$upload = new UploadedFile($source, filesize($source), \UPLOAD_ERR_OK);

		try{
			$upload->moveTo($target);

			Assert::false(file_exists($source));
			Assert::true(file_exists($target));
			Assert::same('file content', file_get_contents($target));

			Assert::exception(function() use ($upload, $target): void{
				$upload->moveTo($target);
			}, \RuntimeException::class, 'Uploaded file has already been moved.');

			Assert::exception(function() use ($upload): void{
				$upload->getStream();
			}, \RuntimeException::class, 'Uploaded file has already been moved.');
		}finally{
			@unlink($target);
		}
	}

	public function testMoveToWithStream(): void{
		$target = TempDir . '/temp_stream_target.txt';

		$stream = Stream::create('streamed content');
		$upload = new UploadedFile($stream, $stream->getSize(), \UPLOAD_ERR_OK);

		try{
			$upload->moveTo($target);

			Assert::true(file_exists($target));
			Assert::same('streamed content', file_get_contents($target));
		}finally{
			@unlink($target);
		}
	}

	public function testMoveToWithEmptyTarget(): void{
		$stream = Stream::create('data');
		$upload = new UploadedFile($stream, $stream->getSize(), \UPLOAD_ERR_OK);

		Assert::exception(function() use ($upload): void{
			$upload->moveTo('');
		}, \InvalidArgumentException::class, 'Target path must not be empty.');
	}

	public function testLazyStream(): void{
		$source = TempDir . '/temp_lazy.txt';
		file_put_contents($source, 'lazy content');

		$upload = new UploadedFile($source, filesize($source));

		try{
			$stream1 = $upload->getStream();
			Assert::type(Stream::class, $stream1);
			Assert::same('lazy content', (string) $stream1);

			// same stream instance
			$stream2 = $upload->getStream();
			Assert::same($stream1, $stream2);
		}finally{
			@unlink($source);
		}
	}

	public function testInvalidUpload(): void{
		$upload = new UploadedFile('non_existent_file.txt', null, \UPLOAD_ERR_NO_FILE);

		Assert::same(\UPLOAD_ERR_NO_FILE, $upload->getError());
		Assert::false($upload->isOk());

		Assert::exception(function() use ($upload): void{
			$upload->getStream();
		}, \RuntimeException::class, 'Uploaded file is not valid due to upload error.');

		Assert::exception(function() use ($upload): void{
			$upload->moveTo('/some/target/path.txt');
		}, \RuntimeException::class, 'Uploaded file is not valid due to upload error.');
	}

}

(new MessageUploadedFileTest)->run();
