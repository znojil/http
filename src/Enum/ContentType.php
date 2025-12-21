<?php
declare(strict_types = 1);

namespace Znojil\Http\Enum;

enum ContentType: string{

	case Csv = 'text/csv';

	case Form = 'application/x-www-form-urlencoded';

	case Html = 'text/html';

	case JavaScript = 'application/javascript';

	case Json = 'application/json';

	case Multipart = 'multipart/form-data';

	case OctetStream = 'application/octet-stream';

	case Pdf = 'application/pdf';

	case Plain = 'text/plain';

	case Xml = 'application/xml';

	public function withCharset(string $charset = 'UTF-8'): string{
		return $this->value . '; charset=' . $charset;
	}

}
