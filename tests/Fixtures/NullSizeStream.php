<?php
declare(strict_types=1);

namespace Znojil\Http\Tests\Fixtures;

final class NullSizeStream extends \Znojil\Http\Message\Stream{

	public function getSize(): ?int{
		return null;
	}

}
