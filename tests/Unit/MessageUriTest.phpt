<?php
declare(strict_types=1);

namespace Znojil\Http\Tests\Unit;

use Tester\Assert;
use Znojil\Http\Message\Uri;

require __DIR__ . '/../bootstrap.php';

/**
 * @testCase
 */
final class MessageUriTest extends \Tester\TestCase{

	public function testFromGlobals(): void{
		$uri = Uri::fromGlobals();

		$_SERVER['HTTPS'] = 'on';
		$_SERVER['HTTP_HOST'] = 'example.com';
		$_SERVER['REQUEST_URI'] = '/path/to/123?q=1';
		$uri2 = Uri::fromGlobals();

		unset($_SERVER['HTTPS']);
		$uri3 = Uri::fromGlobals();

		Assert::same('http://localhost/', (string) $uri);
		Assert::same('https://example.com/path/to/123?q=1', (string) $uri2);
		Assert::same('http://example.com/path/to/123?q=1', (string) $uri3);
	}

	public function testConstructor(): void{
		$uri = new Uri('https://user:pass@example.com:8080/path/to/123?q=1#frag');

		Assert::same('https', $uri->getScheme());
		Assert::same('user:pass@example.com:8080', $uri->getAuthority());
		Assert::same('user:pass', $uri->getUserInfo());
		Assert::same('example.com', $uri->getHost());
		Assert::same(8080, $uri->getPort());
		Assert::same('/path/to/123', $uri->getPath());
		Assert::same('q=1', $uri->getQuery());
		Assert::same('frag', $uri->getFragment());
		Assert::same('https://user:pass@example.com:8080', $uri->getHostUri());
		Assert::same('/path/to/123', $uri->getComposedPath());
		Assert::same('https://user:pass@example.com:8080/path/to/123?q=1#frag', $uri->getAbsoluteUri());

		Assert::same('https://user:pass@example.com:8080/path/to/123?q=1#frag', (string) $uri);
		Assert::same('https://user:pass@example.com:8080/path/to/123?q=1#frag', (string) new Uri($uri));
		Assert::same('', (string) new Uri(''));
		Assert::same('', (string) new Uri);

		Assert::same(['https', 'user:pass', 'example.com', 8080, '/path/to/123', 'q=1', 'frag'], $uri->export());
		Assert::same(['', '', '', null, '', '', ''], (new Uri)->export());

		foreach([
			'http:///',
			'http://:80',
			'http://example.com:invalid/'
		] as $v){
			Assert::exception(
				fn() => new Uri($v),
				\InvalidArgumentException::class,
				'Unable to parse URI: ' . $v
			);
		}
	}

	public function testMutability(): void{
		$uri = new Uri('https://user:pass@example.com:8080/path/to/123?q=1#frag');
		$newUri = $uri
			->withScheme('http')
			->withUserInfo('root', 'pass')
			->withHost('example.cz')
			->withPort(80)
			->withPath('123/to/path')
			->withQuery('1=q')
			->withFragment('graf');

		Assert::same('https', $uri->getScheme());
		Assert::same('user:pass@example.com:8080', $uri->getAuthority());
		Assert::same('user:pass', $uri->getUserInfo());
		Assert::same('example.com', $uri->getHost());
		Assert::same(8080, $uri->getPort());
		Assert::same('/path/to/123', $uri->getPath());
		Assert::same('q=1', $uri->getQuery());
		Assert::same('frag', $uri->getFragment());
		Assert::same('https://user:pass@example.com:8080', $uri->getHostUri());
		Assert::same('/path/to/123', $uri->getComposedPath());
		Assert::same('https://user:pass@example.com:8080/path/to/123?q=1#frag', $uri->getAbsoluteUri());

		Assert::same('http', $newUri->getScheme());
		Assert::same('root:pass@example.cz', $newUri->getAuthority());
		Assert::same('root:pass', $newUri->getUserInfo());
		Assert::same('example.cz', $newUri->getHost());
		Assert::same(null, $newUri->getPort());
		Assert::same('123/to/path', $newUri->getPath());
		Assert::same('1=q', $newUri->getQuery());
		Assert::same('graf', $newUri->getFragment());
		Assert::same('http://root:pass@example.cz', $newUri->getHostUri());
		Assert::same('/123/to/path', $newUri->getComposedPath());
		Assert::same('http://root:pass@example.cz/123/to/path?1=q#graf', $newUri->getAbsoluteUri());
	}

	public function testCombine(): void{
		$uri = new Uri('https://api.site.com/v1?key=123&pass=abc');

		Assert::same('https://api.site.com/v1/users?key=123&pass=abc', (string) $uri->combine(new Uri('users')));
		Assert::same('https://api.site.com/v1/users?key=123&pass=abc', (string) $uri->combine(new Uri('/users')));
		Assert::same('https://api.site.com/v1/users?key=123&pass=abc&limit=5', (string) $uri->combine(new Uri('users?limit=5')));
		Assert::same('https://api.site.com/v1/users?key=321&pass=abc', (string) $uri->combine(new Uri('users?key=321')));

		$uri2 = new Uri('https://google.com:443');
		$uri3 = new Uri('https://google.com:443');
		Assert::same('https://google.com', (string) $uri->combine($uri2));
		Assert::same($uri2, $uri->combine($uri2));
		Assert::notSame($uri3, $uri->combine($uri2));
	}

	public function testPathManipulation(): void{
		Assert::same('https://example.com/path', (string) (new Uri('https://example.com'))->withPath('path'));
		Assert::same('https://example.com/path', (string) (new Uri('https://example.com/'))->withPath('path'));
		Assert::same('https://example.com/path', (string) (new Uri('https://example.com'))->withPath('/path'));
		Assert::same('https://example.com/path', (string) (new Uri('https://example.com/'))->withPath('/path'));
		Assert::same('https://example.com//path', (string) (new Uri('https://example.com/'))->withPath('//path'));
		Assert::same('https://example.com', (string) (new Uri('https://example.com/'))->withPath(''));

		Assert::same('mailto:mail@example.com', (string) (new Uri('mailto:'))->withPath('mail@example.com'));
		Assert::same('mailto:/mail@example.com', (string) (new Uri('mailto:'))->withPath('/mail@example.com'));
		Assert::same('mailto:/mail@example.com', (string) (new Uri('mailto:'))->withPath('//mail@example.com'));
	}

	public function testSame(): void{
		foreach([
			'urn:path-rootless',
			'urn:path:with:colon',
			'urn:/path-absolute',
			'urn:/',
			// only scheme with empty path
			'urn:',
			// only path
			'/',
			'relative/',
			'0',
			// same document reference
			'',
			// network path without scheme
			'//example.org',
			'//example.org/',
			'//example.org?q#h',
			// only query
			'?q',
			'?q=abc&foo=bar',
			// only fragment
			'#fragment',
			// dot segments are not removed automatically
			'./foo/../bar',
		] as $v){
			Assert::same($v, (string) new Uri($v));
		}
	}

	public function testEncoding(): void{
		Assert::same('/path%20with%20spaces', (new Uri)->withPath('/path with spaces')->getPath());
		Assert::same('q=hello%20world', (new Uri)->withQuery('q=hello world')->getQuery());
		Assert::same('/path%20ok', (new Uri)->withPath('/path%20ok')->getPath());
		Assert::same('%C5%A1koda', (new Uri)->withFragment('škoda')->getFragment());
	}

	public function testSchemes(): void{
		foreach([
			['https', 'https', 'https:'],
			['HTTP', 'http', 'http:'],
			['ftp://', 'ftp', 'ftp:'],
			['http:', 'http', 'http:'],
			['http/', 'http', 'http:'],
			['', '', '']
		] as $v){
			$uri = (new Uri)->withScheme($v[0]);
			Assert::same($v[1], $uri->getScheme());
			Assert::contains($v[2], (string) $uri);
		}

		$uri = (new Uri('http://example.com'))->withScheme('');
		Assert::same('', $uri->getScheme());
		Assert::notContains('http', (string) $uri);

		Assert::exception(
			fn() => (new Uri)->withScheme('1http'),
			\InvalidArgumentException::class,
			"Invalid scheme '1http'."
		);
	}

	public function getUserInfoArgs(): array{
		return [
			['root', 'pass', 'root:pass'],
			['root', '', 'root'],
			['', 'pass', '']
		];
	}

	/**
	 * @dataProvider getUserInfoArgs
	 */
	public function testUserInfo(string $user, string $password, string $expected): void{
		Assert::same($expected, (new Uri)->withUserInfo($user, $password)->getUserInfo());
	}

	public function getPortsArgs(): array{
		return [
			['http', null, null],
			['http', 80, null],
			['http', 8080, 8080],
			['https', 443, null],
			['https', 8443, 8443],
			['ftp', 21, null],
			['ftp', 22, 22],
			['custom', null, null],
			['custom', 1234, 1234]
		];
	}

	/**
	 * @dataProvider getPortsArgs
	 */
	public function testPorts(string $scheme, ?int $port, ?int $expected): void{
		Assert::same($expected, (new Uri)->withScheme($scheme)->withPort($port)->getPort());
	}

	public function getQueryArgs(): array{
		return [
			['a=1', 'a=1', '?a=1'],
			['?a=1', 'a=1', '?a=1'],
			['#a=1', '%23a=1', '?%23a=1']
		];
	}

	/**
	 * @dataProvider getQueryArgs
	 */
	public function testQueries(string $query, string $expectedQuery, string $expectedQueryInUri): void{
		$uri = (new Uri)->withQuery($query);
		Assert::same($expectedQuery, $uri->getQuery());
		Assert::contains($expectedQueryInUri, (string) $uri);
	}

	public function getFragmentArgs(): array{
		return [
			['a=1', 'a=1', '#a=1'],
			['#a=1', 'a=1', '#a=1'],
			['?a=1', '?a=1', '#?a=1']
		];
	}

	/**
	 * @dataProvider getFragmentArgs
	 */
	public function testFragments(string $fragment, string $expectedFragment, string $expectedFragmentInUri): void{
		$uri = (new Uri)->withFragment($fragment);
		Assert::same($expectedFragment, $uri->getFragment());
		Assert::contains($expectedFragmentInUri, (string) $uri);
	}

	public function testRemoveComponents(): void{
		$uri = new Uri('https://user:pass@example.com:8080/path/to/123?q=1#frag');

		$uriWithoutScheme = $uri->withScheme('');
		Assert::same('', $uriWithoutScheme->getScheme());
		Assert::same('//user:pass@example.com:8080/path/to/123?q=1#frag', (string) $uriWithoutScheme);

		$uriWithoutUser = $uri->withUserInfo('');
		Assert::same('', $uriWithoutUser->getUserInfo());
		Assert::same('https://example.com:8080/path/to/123?q=1#frag', (string) $uriWithoutUser);

		$uriWithoutUser2 = $uri->withUserInfo('', 'pass');
		Assert::same('', $uriWithoutUser2->getUserInfo());
		Assert::same('https://example.com:8080/path/to/123?q=1#frag', (string) $uriWithoutUser2);

		$uriWithoutHost = $uri->withHost('');
		Assert::same('', $uriWithoutHost->getHost());
		Assert::same('https://user:pass@:8080/path/to/123?q=1#frag', (string) $uriWithoutHost);

		$uriWithoutPort = $uri->withPort(null);
		Assert::same(null, $uriWithoutPort->getPort());
		Assert::same('https://user:pass@example.com/path/to/123?q=1#frag', (string) $uriWithoutPort);

		$uriWithoutPath = $uri->withPath('');
		Assert::same('', $uriWithoutPath->getPath());
		Assert::same('https://user:pass@example.com:8080?q=1#frag', (string) $uriWithoutPath);

		$uriWithoutQuery = $uri->withQuery('');
		Assert::same('', $uriWithoutQuery->getQuery());
		Assert::same('https://user:pass@example.com:8080/path/to/123#frag', (string) $uriWithoutQuery);

		$uriWithoutFragment = $uri->withFragment('');
		Assert::same('', $uriWithoutFragment->getFragment());
		Assert::same('https://user:pass@example.com:8080/path/to/123?q=1', (string) $uriWithoutFragment);
	}

}

(new MessageUriTest)->run();
