<?php
declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

$requestUri = (isset($_SERVER['REQUEST_URI']) && is_string($_SERVER['REQUEST_URI'])) ? $_SERVER['REQUEST_URI'] : '/';
$uriParts = @parse_url($requestUri);
$path = ltrim($uriParts['path'] ?? '', '/');

match($path){
	'json' => (function (): void{
		header('HTTP/3.0 200 OK!');
		header('Foo: Bar');
		header('Foo: baZ', false);

		echo json_encode([
			'method' => $_SERVER['REQUEST_METHOD'],
			'uri' => $_SERVER['REQUEST_URI'],
			'headers' => getallheaders(),
			'body' => file_get_contents('php://input'),
			'query' => $_GET
		]);
	})(),
	'sleep' => (function (): void{
		$seconds = (isset($_GET['s']) && is_int($_GET['s'])) ? (int) $_GET['s'] : 1;
		sleep($seconds);

		echo "Slept for $seconds seconds";
	})(),
	'ping' => (function (): void{
		header('HTTP/2.0 200 ok');

		echo 'pong';
	})(),
	default => (function (): void{
		http_response_code(404);

		echo 'Endpoint not found';
	})()
};
