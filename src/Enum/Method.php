<?php
declare(strict_types = 1);

namespace Znojil\Http\Enum;

enum Method: string{

	case Delete = 'DELETE';

	case Get = 'GET';

	case Head = 'HEAD';

	case Options = 'OPTIONS';

	case Patch = 'PATCH';

	case Post = 'POST';

	case Put = 'PUT';

}
