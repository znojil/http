<?php
declare(strict_types=1);

namespace Znojil\Http\Tests\Unit;

use Tester\Assert;

require __DIR__ . '/../bootstrap.php';

/**
 * @testCase
 */
final class EnumContentTypeTest extends \Tester\TestCase{

	public function testConstructor(): void{
		$ct = \Znojil\Http\Enum\ContentType::Json;

		Assert::same('application/json', $ct->value);
		Assert::same('application/json; charset=UTF-8', $ct->withCharset());
		Assert::same('application/json; charset=utf-8', $ct->withCharset('utf-8'));
	}

}

(new EnumContentTypeTest)->run();
