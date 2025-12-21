<?php
declare(strict_types=1);

namespace Znojil\Http\Internal;

final class ResourceUtil{

	/**
	 * @return resource
	 * @throws \RuntimeException
	 */
	public static function tryFopen(string $filename, string $mode){
		if (!($resource = @fopen($filename, $mode))){
			throw new \RuntimeException("Failed to open resource '$filename' with mode '$mode'. Check permissions or available memory.");
		}

		return $resource;
	}

}
