<?php
declare(strict_types=1);

namespace Znojil\Http\Message;

use Psr\Http\Message\UriInterface;

class Uri implements UriInterface{

	/** @var array<string, int> */
	public static array $defaultPorts = [
		'http' => 80,
		'https' => 443,
		'ftp' => 21,
		'ssh' => 22
	];

	public static function fromGlobals(): self{
		$scheme = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
		$httpHost = (isset($_SERVER['HTTP_HOST']) && is_string($_SERVER['HTTP_HOST']))
			? $_SERVER['HTTP_HOST']
			: 'localhost';
		$requestUri = (isset($_SERVER['REQUEST_URI']) && is_string($_SERVER['REQUEST_URI']))
			? $_SERVER['REQUEST_URI']
			: '/';

		return new self($scheme . '://' . $httpHost . $requestUri);
	}

	private const CharUnreserved = 'a-zA-Z0-9_\-\.\~';

	private const CharSubDelims = '!\$&\'\(\)\*\+,;=';

	private const CharPath = self::CharUnreserved . self::CharSubDelims . ':@\/';

	private const CharQuery = self::CharPath . '\?';

	private const CharUserInfo = self::CharUnreserved . self::CharSubDelims;

	private string $scheme = '';

	private string $userInfo = '';

	private string $host = '';

	private ?int $port = null;

	private string $path = '';

	private string $query = '';

	private string $fragment = '';

	public function __construct(string|self|null $uri = null){
		if(is_string($uri)){
			$parts = @parse_url($uri);
			if($parts === false){
				throw new \InvalidArgumentException('Unable to parse URI: ' . $uri);
			}

			$this->scheme = isset($parts['scheme']) ? $this->filterScheme($parts['scheme']) : '';
			$this->userInfo = (isset($parts['user']) ? $this->filterUserInfoComponent($parts['user']) : '') .
				(isset($parts['pass']) ? ':' . $this->filterUserInfoComponent($parts['pass']) : '');
			$this->host = isset($parts['host']) ? $this->filterHost($parts['host']) : '';
			$this->port = isset($parts['port']) ? $this->filterPort($this->scheme, $parts['port']) : null;
			$this->path = isset($parts['path']) ? $this->filterPath($parts['path']) : '';
			$this->query = isset($parts['query']) ? $this->filterQueryAndFragment($parts['query']) : '';
			$this->fragment = isset($parts['fragment']) ? $this->filterQueryAndFragment($parts['fragment']) : '';
		}elseif($uri instanceof self){
			[$this->scheme, $this->userInfo, $this->host, $this->port, $this->path, $this->query, $this->fragment] = $uri->export();
		}
	}

	public function getScheme(): string{
		return $this->scheme;
	}

	public function getAuthority(): string{
		$authority = $this->host;
		if(($userInfo = $this->getUserInfo()) !== ''){
			$authority = $userInfo . '@' . $authority;
		}

		if(($port = $this->getPort()) !== null){
			$authority .= ':' . $port;
		}

		return $authority;
	}

	public function getUserInfo(): string{
		return $this->userInfo;
	}

	public function getHost(): string{
		return $this->host;
	}

	public function getPort(): ?int{
		return $this->port;
	}

	public function getPath(): string{
		return $this->path;
	}

	public function getQuery(): string{
		return $this->query;
	}

	public function getFragment(): string{
		return $this->fragment;
	}

	public function withScheme(string $scheme): static{
		$scheme = $this->filterScheme($scheme);
		if($scheme === $this->scheme){
			return $this;
		}

		$new = clone $this;
		$new->scheme = $scheme;
		$new->port = $new->filterPort($new->scheme, $new->port);

		return $new;
	}

	public function withUserInfo(string $user, ?string $password = null): static{
		$userInfo = $this->filterUserInfoComponent($user);
		if($password !== null){
			$userInfo .= ':' . $this->filterUserInfoComponent($password);
		}

		if($userInfo === $this->userInfo){
			return $this;
		}

		$new = clone $this;
		$new->userInfo = $userInfo;

		return $new;
	}

	public function withHost(string $host): static{
		$host = $this->filterHost($host);
		if($host === $this->host){
			return $this;
		}

		$new = clone $this;
		$new->host = $host;

		return $new;
	}

	public function withPort(?int $port): static{
		$port = $this->filterPort($this->scheme, $port);
		if($port === $this->port){
			return $this;
		}

		$new = clone $this;
		$new->port = $port;

		return $new;
	}

	public function withPath(string $path): static{
		$path = $this->filterPath($path);
		if($path === $this->path){
			return $this;
		}

		$new = clone $this;
		$new->path = $path;

		return $new;
	}

	public function withQuery(string $query): static{
		$query = $this->filterQueryAndFragment($query);
		if($query === $this->query){
			return $this;
		}

		$new = clone $this;
		$new->query = $query;

		return $new;
	}

	public function withFragment(string $fragment): static{
		$fragment = $this->filterQueryAndFragment($fragment);
		if($fragment === $this->fragment){
			return $this;
		}

		$new = clone $this;
		$new->fragment = $fragment;

		return $new;
	}

	public function __toString(): string{
		return $this->getAbsoluteUri();
	}

	public function getHostUri(): string{
		$hostUrl = '';
		if($this->scheme !== ''){
			$hostUrl .= $this->scheme . ':';
		}

		if(($authority = $this->getAuthority()) !== ''){
			$hostUrl .= '//' . $authority;
		}

		return $hostUrl;
	}

	public function getComposedPath(): string{
		$path = $this->getPath();
		$authority = $this->getAuthority();

		if($authority !== '' && $path !== '' && !str_starts_with($path, '/')){
			$path = '/' . $path;
		}elseif($authority === '' && str_starts_with($path, '//')){
			$path = '/' . ltrim($path, '/');
		}

		return $path;
	}

	public function getAbsoluteUri(): string{
		$uri = $this->getHostUri() . $this->getComposedPath();

		if($this->query !== ''){
			$uri .= '?' . $this->query;
		}

		if($this->fragment !== ''){
			$uri .= '#' . $this->fragment;
		}

		return $uri;
	}

	/**
	 * Only combine, no resolve by RFC 3986.
	 */
	public function combine(UriInterface $relative): static|UriInterface{
		if($relative->getScheme() !== ''){
			return $relative;
		}

		if($relative->getAuthority() !== ''){
			return $relative->withScheme($this->getScheme());
		}

		$newUri = clone $this;

		$relPath = $relative->getPath();
		if($relPath !== ''){
			$newPath = $relPath;

			$basePath = $this->getPath();
			if($basePath !== ''){
				$newPath = rtrim($basePath, '/') . '/' . ltrim($relPath, '/');
			}

			$newUri = $newUri->withPath($newPath);
		}

		$newQuery = $relative->getQuery();
		if($newQuery !== ''){
			if(($baseQuery = $this->getQuery()) !== ''){
				parse_str($baseQuery, $baseQueryParams);
				parse_str($newQuery, $newQueryParams);

				$newQuery = http_build_query(array_merge($baseQueryParams, $newQueryParams), '', '&', PHP_QUERY_RFC3986);
			}

			$newUri = $newUri->withQuery($newQuery);
		}

		return $newUri->withFragment($relative->getFragment());
	}

	/**
	 * @internal
	 * @return array{string, string, string, ?int, string, string, string}
	 */
	final public function export(): array{
		return [$this->scheme, $this->userInfo, $this->host, $this->port, $this->path, $this->query, $this->fragment];
	}

	private function filterScheme(string $scheme): string{
		return strtolower($scheme);
	}

	private function filterUserInfoComponent(string $component): string{
		return $this->encode($component, self::CharUserInfo);
	}

	private function filterHost(string $host): string{
		return strtolower($host);
	}

	private function filterPort(string $scheme, ?int $port): ?int{
		if($port === null){
			return null;
		}

		if($port < 1 || $port > 0xffff){
			throw new \InvalidArgumentException("Invalid port '$port'. Must be between 1 and 65535.");
		}

		if(isset(self::$defaultPorts[$scheme]) && self::$defaultPorts[$scheme] === $port){
			return null;
		}

		return $port;
	}

	private function filterPath(string $path): string{
		return $this->encode($path, self::CharPath);
	}

	private function filterQueryAndFragment(string $str): string{
		return $this->encode($str, self::CharQuery);
	}

	private function encode(string $str, string $allowedChars): string{
		/** @var string */
		return preg_replace_callback(
			'~(?:[^' . $allowedChars . '%]+|%(?![A-Fa-f0-9]{2}))~',
			fn(array $m): string => rawurlencode($m[0]),
			$str
		);
	}

}
