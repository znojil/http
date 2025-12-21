<?php
declare(strict_types = 1);

namespace Znojil\Http\Exception;

class ClientException extends \RuntimeException implements \Psr\Http\Client\ClientExceptionInterface{}
